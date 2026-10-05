'use server'

import { revalidatePath } from 'next/cache'
import { createServerClient } from '@/lib/supabase/server'
import type { OrderStatus } from '@/types'

// ─── State machine: allowed transitions ──────────────────────────────────────

const TRANSITIONS: Record<OrderStatus, OrderStatus[]> = {
  pending:    ['confirmed', 'cancelled'],
  confirmed:  ['dispatched', 'cancelled'],
  dispatched: ['in_transit', 'failed', 'cancelled'],
  in_transit: ['delivered', 'failed', 'returned'],
  delivered:  [],
  failed:     ['in_transit', 'returned', 'cancelled'],
  returned:   [],
  cancelled:  [],
}

export type TransitionResult = { ok: true } | { ok: false; error: string }

export async function transitionOrderStatus(
  orderId: string,
  newStatus: OrderStatus,
  notes?: string
): Promise<TransitionResult> {
  const supabase = createServerClient()
  const { data: { session } } = await supabase.auth.getSession()
  if (!session) return { ok: false, error: 'Not authenticated' }

  // Fetch current order
  const { data: order, error: fetchErr } = await supabase
    .from('orders')
    .select('status')
    .eq('id', orderId)
    .single()

  if (fetchErr || !order) return { ok: false, error: 'Order not found' }

  const allowed = TRANSITIONS[order.status as OrderStatus] ?? []
  if (!allowed.includes(newStatus)) {
    return {
      ok: false,
      error: `Cannot transition from "${order.status}" to "${newStatus}"`,
    }
  }

  // Build timestamp fields
  const timestamps: Record<string, string> = {}
  if (newStatus === 'confirmed')  timestamps.confirmed_at  = new Date().toISOString()
  if (newStatus === 'dispatched') timestamps.dispatched_at = new Date().toISOString()
  if (newStatus === 'delivered')  timestamps.delivered_at  = new Date().toISOString()

  // Update order
  const { error: updateErr } = await supabase
    .from('orders')
    .update({ status: newStatus, ...timestamps })
    .eq('id', orderId)

  if (updateErr) return { ok: false, error: updateErr.message }

  // Write audit log
  await supabase.from('order_status_history').insert({
    order_id:   orderId,
    old_status: order.status,
    new_status: newStatus,
    changed_by: session.user.id,
    notes,
  })

  revalidatePath(`/dashboard/orders/${orderId}`)
  revalidatePath('/dashboard/orders')
  revalidatePath('/dashboard')
  return { ok: true }
}

// ─── Pricing engine ──────────────────────────────────────────────────────────

export interface PricingResult {
  line_items: Array<{ label: string; amount: number }>
  subtotal: number
  tax: number
  total: number
  currency: string
  rate_card_id: string | null
}

export async function calculateOrderPrice(
  clientId: string | null,
  totalItems: number,
  totalWeightKg: number | null,
  deliveryAddress: string
): Promise<PricingResult> {
  const supabase = createServerClient()

  const fallback: PricingResult = {
    line_items: [{ label: 'Base delivery fee', amount: 5000 }],
    subtotal: 5000,
    tax: 375,
    total: 5375,
    currency: 'NGN',
    rate_card_id: null,
  }

  if (!clientId) return fallback

  // Fetch the client's active rate card
  const { data: rateCard } = await supabase
    .from('rate_cards')
    .select('*')
    .eq('client_id', clientId)
    .lte('effective_from', new Date().toISOString().slice(0, 10))
    .or('effective_to.is.null,effective_to.gte.' + new Date().toISOString().slice(0, 10))
    .order('is_default', { ascending: false })
    .limit(1)
    .maybeSingle()

  if (!rateCard) return fallback

  let amount = 0
  let label = ''

  switch (rateCard.rate_type) {
    case 'per_delivery':
      amount = rateCard.rate_value
      label = 'Per-delivery fee'
      break
    case 'per_pallet':
      amount = rateCard.rate_value * Math.ceil(totalItems / 10)
      label = `Per-pallet fee (${Math.ceil(totalItems / 10)} pallets)`
      break
    case 'flat_fee':
      amount = rateCard.rate_value
      label = 'Flat fee'
      break
    case 'per_km':
      // Distance placeholder — actual routing will supply this
      amount = rateCard.rate_value * 20
      label = 'Distance-based fee (estimated 20km)'
      break
    case 'per_zone':
      amount = rateCard.rate_value
      label = `Zone fee — ${rateCard.zone ?? 'standard'}`
      break
    default:
      return { ...fallback, rate_card_id: rateCard.id }
  }

  // Apply min/max guards
  if (rateCard.min_charge && amount < rateCard.min_charge) amount = rateCard.min_charge
  if (rateCard.max_charge && amount > rateCard.max_charge) amount = rateCard.max_charge

  const tax = Math.round(amount * 0.075) // 7.5% VAT
  return {
    line_items: [{ label, amount }],
    subtotal: amount,
    tax,
    total: amount + tax,
    currency: rateCard.currency ?? 'NGN',
    rate_card_id: rateCard.id,
  }
}

// ─── Failed delivery ─────────────────────────────────────────────────────────

export type FailureReasonCode =
  | 'no_one_home'
  | 'wrong_address'
  | 'refused'
  | 'damaged'
  | 'access_denied'
  | 'other'

export async function recordFailedDelivery(
  orderId: string,
  deliveryId: string,
  reasonCode: FailureReasonCode,
  notes: string,
  rescheduleDate?: string
): Promise<TransitionResult> {
  const supabase = createServerClient()
  const { data: { session } } = await supabase.auth.getSession()
  if (!session) return { ok: false, error: 'Not authenticated' }

  // Update delivery record
  await supabase.from('deliveries').update({
    status: rescheduleDate ? 'rescheduled' : 'failed',
    failure_reason: notes,
    reschedule_date: rescheduleDate ?? null,
  }).eq('id', deliveryId)

  // Insert failed delivery log
  await supabase.from('failed_deliveries').insert({
    delivery_id:    deliveryId,
    attempt_number: 1,
    reason_code:    reasonCode,
    notes,
    rescheduled_for: rescheduleDate ?? null,
    resolution:      rescheduleDate ? 'rescheduled' : 'pending',
    created_by:      session.user.id,
  })

  // Transition order status
  const result = await transitionOrderStatus(orderId, 'failed', `${reasonCode}: ${notes}`)

  revalidatePath(`/dashboard/orders/${orderId}`)
  revalidatePath('/dashboard/orders')
  return result
}
