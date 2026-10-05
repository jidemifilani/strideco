'use client'

import { useState, useEffect } from 'react'
import { useRouter, useParams } from 'next/navigation'
import { createClient } from '@/lib/supabase/client'
import { Card, CardHeader, CardTitle, CardBody } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { ClientSelector } from '@/components/orders/client-selector'
import { OrderItemsForm, type LineItem } from '@/components/orders/order-items-form'
import Link from 'next/link'
import { ArrowLeft } from 'lucide-react'

export default function EditOrderPage() {
  const params = useParams<{ id: string }>()
  const router = useRouter()
  const supabase = createClient()

  const [loading, setLoading] = useState(false)
  const [fetching, setFetching] = useState(true)
  const [error, setError] = useState('')

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
  const [existingStatus, setExistingStatus] = useState('')

  useEffect(() => {
    async function load() {
      const { data } = await supabase
        .from('orders')
        .select('*, order_items(*), clients_3pl(id, company_name)')
        .eq('id', params.id)
        .single()

      if (!data) { setFetching(false); return }

      setExistingStatus(data.status)
      setForm({
        client_id:       data.client_id ?? '',
        client_name:     (data as any).clients_3pl?.company_name ?? '',
        pickup_address:  data.pickup_address ?? '',
        delivery_address: data.delivery_address ?? '',
        delivery_date:   data.delivery_date ?? '',
        sla_hours:       data.sla_hours ?? 24,
        priority:        data.priority ?? 'normal',
        notes:           data.notes ?? '',
      })
      setItems(
        ((data as any).order_items ?? []).map((i: any) => ({
          id:          i.id,
          description: i.description,
          quantity:    i.quantity,
          weight_kg:   i.weight_kg?.toString() ?? '',
          barcode:     i.barcode ?? '',
          sku:         i.sku ?? '',
        }))
      )
      setFetching(false)
    }
    load()
  }, [params.id])

  function setField(key: string, value: string | number) {
    setForm(prev => ({ ...prev, [key]: value }))
  }

  const canEdit = !['delivered', 'cancelled', 'returned'].includes(existingStatus)

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (!canEdit) return
    setLoading(true)
    setError('')

    const totalItems = items.reduce((s, i) => s + (i.quantity || 0), 0)
    const totalWeight = items.reduce((s, i) => s + (parseFloat(i.weight_kg) || 0), 0)

    const { error: updateErr } = await supabase
      .from('orders')
      .update({
        client_id:        form.client_id || null,
        pickup_address:   form.pickup_address,
        delivery_address: form.delivery_address,
        delivery_date:    form.delivery_date || null,
        sla_hours:        form.sla_hours,
        priority:         form.priority,
        total_items:      totalItems,
        total_weight_kg:  totalWeight || null,
        notes:            form.notes || null,
      })
      .eq('id', params.id)

    if (updateErr) { setError(updateErr.message); setLoading(false); return }

    // Replace all order items
    await supabase.from('order_items').delete().eq('order_id', params.id)
    if (items.length > 0) {
      await supabase.from('order_items').insert(
        items.map(item => ({
          order_id:    params.id,
          description: item.description,
          quantity:    item.quantity,
          weight_kg:   parseFloat(item.weight_kg) || null,
          barcode:     item.barcode || null,
          sku:         item.sku || null,
        }))
      )
    }

    router.push(`/dashboard/orders/${params.id}`)
    router.refresh()
  }

  if (fetching) {
    return <div className="flex items-center justify-center py-20 text-gray-400">Loading order…</div>
  }

  return (
    <div className="max-w-3xl mx-auto space-y-6">
      <div className="flex items-center gap-3">
        <Link href={`/dashboard/orders/${params.id}`} className="p-2 hover:bg-gray-100 rounded-lg transition-colors">
          <ArrowLeft size={18} className="text-gray-600" />
        </Link>
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Edit Order</h1>
          {!canEdit && (
            <p className="text-sm text-amber-600 mt-0.5">
              This order cannot be edited (status: {existingStatus})
            </p>
          )}
        </div>
      </div>

      {!canEdit ? (
        <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 text-amber-800 text-sm">
          Orders with status <strong>{existingStatus}</strong> are locked and cannot be modified.
        </div>
      ) : (
        <form onSubmit={handleSubmit} className="space-y-6">
          {error && (
            <div className="p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">{error}</div>
          )}

          <Card>
            <CardHeader><CardTitle>Client & Scheduling</CardTitle></CardHeader>
            <CardBody className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1.5">Client</label>
                <ClientSelector
                  value={form.client_id}
                  onChange={(id, name) => setField('client_id', id)}
                />
              </div>
              <div className="grid grid-cols-3 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1.5">Delivery Date</label>
                  <input type="date" value={form.delivery_date} onChange={e => setField('delivery_date', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1.5">SLA</label>
                  <select value={form.sla_hours} onChange={e => setField('sla_hours', parseInt(e.target.value))}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    {[4, 8, 24, 48, 72].map(h => <option key={h} value={h}>{h} hours</option>)}
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1.5">Priority</label>
                  <select value={form.priority} onChange={e => setField('priority', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    {['low','normal','high','urgent'].map(p => <option key={p} value={p} className="capitalize">{p}</option>)}
                  </select>
                </div>
              </div>
            </CardBody>
          </Card>

          <Card>
            <CardHeader><CardTitle>Addresses</CardTitle></CardHeader>
            <CardBody className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1.5">Pickup Address *</label>
                <input type="text" required value={form.pickup_address} onChange={e => setField('pickup_address', e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1.5">Delivery Address *</label>
                <input type="text" required value={form.delivery_address} onChange={e => setField('delivery_address', e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
              </div>
            </CardBody>
          </Card>

          <Card>
            <CardHeader><CardTitle>Items Manifest</CardTitle></CardHeader>
            <CardBody>
              <OrderItemsForm items={items} onChange={setItems} />
            </CardBody>
          </Card>

          <Card>
            <CardHeader><CardTitle>Notes</CardTitle></CardHeader>
            <CardBody>
              <textarea value={form.notes} onChange={e => setField('notes', e.target.value)} rows={3}
                placeholder="Special handling instructions…"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </CardBody>
          </Card>

          <div className="flex justify-end gap-3">
            <Link href={`/dashboard/orders/${params.id}`}>
              <Button variant="outline" type="button">Discard</Button>
            </Link>
            <Button type="submit" loading={loading}>Save Changes</Button>
          </div>
        </form>
      )}
    </div>
  )
}
