'use client'

import { useState, useCallback } from 'react'
import { useRouter } from 'next/navigation'
import { createClient } from '@/lib/supabase/client'
import { Card, CardHeader, CardTitle, CardBody } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { ClientSelector } from '@/components/orders/client-selector'
import { OrderItemsForm, type LineItem } from '@/components/orders/order-items-form'
import { PricingDisplay } from '@/components/orders/pricing-display'
import { calculateOrderPrice, type PricingResult } from '@/lib/orders/actions'
import { generateOrderNumber } from '@/lib/utils'
import Link from 'next/link'
import { ArrowLeft, MapPin } from 'lucide-react'

export default function NewOrderPage() {
  const router = useRouter()
  const supabase = createClient()
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [pricing, setPricing] = useState<PricingResult | null>(null)
  const [pricingLoading, setPricingLoading] = useState(false)

  const [form, setForm] = useState({
    client_id: '',
    client_name: '',
    pickup_address: '',
    delivery_address: '',
    delivery_date: '',
    sla_hours: 24,
    priority: 'normal',
    notes: '',
  })
  const [items, setItems] = useState<LineItem[]>([])

  function setField(key: string, value: string | number) {
    setForm(prev => ({ ...prev, [key]: value }))
  }

  const handleClientChange = useCallback(async (id: string, name: string) => {
    setForm(prev => ({ ...prev, client_id: id, client_name: name }))
    if (!id) { setPricing(null); return }

    setPricingLoading(true)
    const totalWeight = items.reduce((s, i) => s + (parseFloat(i.weight_kg) || 0), 0)
    const result = await calculateOrderPrice(id, items.length || 1, totalWeight || null, form.delivery_address)
    setPricing(result)
    setPricingLoading(false)
  }, [items, form.delivery_address])

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (items.length === 0) {
      setError('Add at least one item to the manifest')
      return
    }

    setLoading(true)
    setError('')

    const { data: { session } } = await supabase.auth.getSession()
    if (!session) { setError('Not authenticated'); setLoading(false); return }

    const totalItems = items.reduce((s, i) => s + (i.quantity || 0), 0)
    const totalWeight = items.reduce((s, i) => s + (parseFloat(i.weight_kg) || 0), 0)

    const { data: order, error: orderErr } = await supabase
      .from('orders')
      .insert({
        order_number:    generateOrderNumber(),
        client_id:       form.client_id || null,
        pickup_address:  form.pickup_address,
        delivery_address: form.delivery_address,
        delivery_date:   form.delivery_date || null,
        sla_hours:       form.sla_hours,
        priority:        form.priority,
        total_items:     totalItems,
        total_weight_kg: totalWeight || null,
        notes:           form.notes || null,
        status:          'pending',
        created_by:      session.user.id,
      })
      .select('id')
      .single()

    if (orderErr || !order) {
      setError(orderErr?.message ?? 'Failed to create order')
      setLoading(false)
      return
    }

    // Insert order items
    if (items.length > 0) {
      await supabase.from('order_items').insert(
        items.map(item => ({
          order_id:    order.id,
          description: item.description,
          quantity:    item.quantity,
          weight_kg:   parseFloat(item.weight_kg) || null,
          barcode:     item.barcode || null,
          sku:         item.sku || null,
        }))
      )
    }

    // Write initial status history
    await supabase.from('order_status_history').insert({
      order_id:   order.id,
      old_status: null,
      new_status: 'pending',
      changed_by: session.user.id,
      notes:      'Order created',
    })

    router.push(`/dashboard/orders/${order.id}`)
    router.refresh()
  }

  return (
    <div className="max-w-3xl mx-auto space-y-6">
      <div className="flex items-center gap-3">
        <Link href="/dashboard/orders" className="p-2 hover:bg-gray-100 rounded-lg transition-colors">
          <ArrowLeft size={18} className="text-gray-600" />
        </Link>
        <div>
          <h1 className="text-2xl font-bold text-gray-900">New Delivery Order</h1>
          <p className="text-gray-500 text-sm">All required fields marked with *</p>
        </div>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        {error && (
          <div className="p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">{error}</div>
        )}

        {/* Client & Scheduling */}
        <Card>
          <CardHeader><CardTitle>Client & Scheduling</CardTitle></CardHeader>
          <CardBody className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">Client</label>
              <ClientSelector
                value={form.client_id}
                onChange={handleClientChange}
              />
              <p className="text-xs text-gray-400 mt-1">Only KYC-approved clients are shown</p>
            </div>

            <div className="grid grid-cols-3 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1.5">Delivery Date</label>
                <input
                  type="date"
                  value={form.delivery_date}
                  onChange={e => setField('delivery_date', e.target.value)}
                  min={new Date().toISOString().slice(0, 10)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1.5">SLA *</label>
                <select
                  value={form.sla_hours}
                  onChange={e => setField('sla_hours', parseInt(e.target.value))}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value={4}>4 hours</option>
                  <option value={8}>8 hours</option>
                  <option value={24}>24 hours</option>
                  <option value={48}>48 hours</option>
                  <option value={72}>72 hours</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1.5">Priority</label>
                <select
                  value={form.priority}
                  onChange={e => setField('priority', e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="low">Low</option>
                  <option value="normal">Normal</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
            </div>
          </CardBody>
        </Card>

        {/* Addresses */}
        <Card>
          <CardHeader><CardTitle>Pickup & Delivery Addresses</CardTitle></CardHeader>
          <CardBody className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">
                <span className="flex items-center gap-1.5"><MapPin size={13} className="text-blue-500" /> Pickup Address *</span>
              </label>
              <input
                type="text"
                value={form.pickup_address}
                onChange={e => setField('pickup_address', e.target.value)}
                required
                placeholder="Warehouse / collection address"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">
                <span className="flex items-center gap-1.5"><MapPin size={13} className="text-green-500" /> Delivery Address *</span>
              </label>
              <input
                type="text"
                value={form.delivery_address}
                onChange={e => setField('delivery_address', e.target.value)}
                required
                placeholder="Full delivery destination"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </CardBody>
        </Card>

        {/* Items Manifest */}
        <Card>
          <CardHeader><CardTitle>Items Manifest *</CardTitle></CardHeader>
          <CardBody>
            <OrderItemsForm items={items} onChange={setItems} />
          </CardBody>
        </Card>

        {/* Notes + Pricing side-by-side */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <Card>
            <CardHeader><CardTitle>Notes</CardTitle></CardHeader>
            <CardBody>
              <textarea
                value={form.notes}
                onChange={e => setField('notes', e.target.value)}
                rows={4}
                placeholder="Special handling, fragile items, access codes…"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </CardBody>
          </Card>

          <div className="flex flex-col gap-4">
            <h3 className="text-sm font-medium text-gray-700">Pricing Estimate</h3>
            <PricingDisplay pricing={pricing} loading={pricingLoading} />
          </div>
        </div>

        {/* Actions */}
        <div className="flex justify-end gap-3">
          <Link href="/dashboard/orders">
            <Button variant="outline" type="button">Cancel</Button>
          </Link>
          <Button type="submit" loading={loading}>
            Create Order
          </Button>
        </div>
      </form>
    </div>
  )
}
