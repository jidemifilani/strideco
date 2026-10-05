import { createServerClient } from '@/lib/supabase/server'
import { Card, CardHeader, CardTitle, CardBody } from '@/components/ui/card'
import { StatusBadge } from '@/components/ui/badge'
import { SLAIndicator } from '@/components/orders/sla-indicator'
import { StatusActions } from '@/components/orders/status-actions'
import { OrderDetailFailedButton } from '@/components/orders/order-detail-failed-button'
import { formatDate, formatDateTime, formatCurrency } from '@/lib/utils'
import Link from 'next/link'
import { ArrowLeft, MapPin, Package, Clock, Building2, CheckCircle2 } from 'lucide-react'
import { notFound } from 'next/navigation'
import type { OrderStatus } from '@/types'

export default async function OrderDetailPage({ params }: { params: { id: string } }) {
  const supabase = createServerClient()

  const [orderRes, deliveriesRes, historyRes] = await Promise.all([
    supabase
      .from('orders')
      .select(`
        *,
        clients_3pl(id, company_name, contact_name, contact_email, contact_phone, tier),
        order_items(*)
      `)
      .eq('id', params.id)
      .single(),

    supabase
      .from('deliveries')
      .select('*, drivers(profiles(full_name)), pod_records(*)')
      .eq('order_id', params.id)
      .order('created_at'),

    supabase
      .from('order_status_history')
      .select('*, profiles(full_name)')
      .eq('order_id', params.id)
      .order('created_at'),
  ])

  if (orderRes.error || !orderRes.data) notFound()
  const order = orderRes.data as any
  const deliveries = deliveriesRes.data ?? []
  const history = historyRes.data ?? []

  const latestDelivery = deliveries[deliveries.length - 1]

  return (
    <div className="max-w-5xl mx-auto space-y-6">
      {/* Page header */}
      <div className="flex items-center gap-3">
        <Link href="/dashboard/orders" className="p-2 hover:bg-gray-100 rounded-lg transition-colors">
          <ArrowLeft size={18} className="text-gray-600" />
        </Link>
        <div className="flex-1">
          <div className="flex items-center gap-3 flex-wrap">
            <h1 className="text-xl font-bold text-gray-900 font-mono">{order.order_number}</h1>
            <StatusBadge status={order.status} />
            {order.priority !== 'normal' && (
              <span className={`text-xs px-2 py-0.5 rounded-full font-medium capitalize border ${
                order.priority === 'urgent' ? 'bg-red-50 text-red-700 border-red-200' :
                order.priority === 'high'   ? 'bg-orange-50 text-orange-700 border-orange-200' :
                'bg-gray-50 text-gray-600 border-gray-200'
              }`}>
                {order.priority}
              </span>
            )}
          </div>
          <p className="text-gray-500 text-sm mt-0.5">Created {formatDateTime(order.created_at)}</p>
        </div>
        <Link
          href={`/dashboard/orders/${params.id}/edit`}
          className="text-sm text-blue-600 hover:underline"
        >
          Edit order
        </Link>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* ── Main column ─────────────────────────────────────── */}
        <div className="lg:col-span-2 space-y-5">

          {/* SLA */}
          <SLAIndicator
            createdAt={order.created_at}
            slaHours={order.sla_hours}
            status={order.status}
            className="bg-white border border-gray-200 rounded-xl p-4"
          />

          {/* Shipment details */}
          <Card>
            <CardHeader><CardTitle>Shipment Details</CardTitle></CardHeader>
            <CardBody className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">Pickup</p>
                  <div className="flex items-start gap-2">
                    <MapPin size={14} className="text-blue-500 mt-0.5 shrink-0" />
                    <p className="text-sm text-gray-700 leading-relaxed">{order.pickup_address}</p>
                  </div>
                </div>
                <div>
                  <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">Delivery</p>
                  <div className="flex items-start gap-2">
                    <MapPin size={14} className="text-green-500 mt-0.5 shrink-0" />
                    <p className="text-sm text-gray-700 leading-relaxed">{order.delivery_address}</p>
                  </div>
                </div>
              </div>

              <div className="grid grid-cols-4 gap-4 pt-3 border-t border-gray-100">
                <div>
                  <p className="text-xs text-gray-500 mb-1">Items</p>
                  <p className="text-sm font-semibold text-gray-900 flex items-center gap-1">
                    <Package size={13} className="text-gray-400" />
                    {order.total_items}
                  </p>
                </div>
                <div>
                  <p className="text-xs text-gray-500 mb-1">Weight</p>
                  <p className="text-sm font-semibold text-gray-900">
                    {order.total_weight_kg ? `${order.total_weight_kg}kg` : '—'}
                  </p>
                </div>
                <div>
                  <p className="text-xs text-gray-500 mb-1">SLA</p>
                  <p className="text-sm font-semibold text-gray-900 flex items-center gap-1">
                    <Clock size={13} className="text-gray-400" />
                    {order.sla_hours}h
                  </p>
                </div>
                <div>
                  <p className="text-xs text-gray-500 mb-1">Delivery Date</p>
                  <p className="text-sm font-semibold text-gray-900">
                    {order.delivery_date ? formatDate(order.delivery_date) : '—'}
                  </p>
                </div>
              </div>

              {order.notes && (
                <div className="pt-3 border-t border-gray-100">
                  <p className="text-xs text-gray-500 mb-1 uppercase tracking-wide">Notes</p>
                  <p className="text-sm text-gray-700">{order.notes}</p>
                </div>
              )}
            </CardBody>
          </Card>

          {/* Items Manifest */}
          {order.order_items?.length > 0 && (
            <Card>
              <CardHeader>
                <CardTitle>Items Manifest ({order.order_items.length})</CardTitle>
                <span className="text-xs text-gray-500">
                  {order.total_items} units · {order.total_weight_kg ?? '—'}kg
                </span>
              </CardHeader>
              <CardBody className="p-0">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-gray-100 bg-gray-50">
                      <th className="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase">Description</th>
                      <th className="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase">Qty</th>
                      <th className="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase">Weight</th>
                      <th className="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase">Barcode</th>
                      <th className="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase">SKU</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-50">
                    {order.order_items.map((item: any) => (
                      <tr key={item.id} className="hover:bg-gray-50">
                        <td className="px-5 py-3 text-gray-700">{item.description}</td>
                        <td className="px-5 py-3 text-gray-700 tabular-nums">{item.quantity}</td>
                        <td className="px-5 py-3 text-gray-600">{item.weight_kg ? `${item.weight_kg}kg` : '—'}</td>
                        <td className="px-5 py-3 font-mono text-xs text-gray-500">{item.barcode ?? '—'}</td>
                        <td className="px-5 py-3 font-mono text-xs text-gray-500">{item.sku ?? '—'}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </CardBody>
            </Card>
          )}

          {/* Delivery attempts */}
          {deliveries.length > 0 && (
            <Card>
              <CardHeader>
                <CardTitle>Delivery Attempts ({deliveries.length})</CardTitle>
              </CardHeader>
              <CardBody className="space-y-3">
                {deliveries.map((d: any, i: number) => (
                  <div key={d.id} className="p-3 bg-gray-50 rounded-xl border border-gray-100">
                    <div className="flex items-center justify-between mb-1.5">
                      <span className="text-sm font-semibold text-gray-900">Attempt #{i + 1}</span>
                      <StatusBadge status={d.status} />
                    </div>
                    <p className="text-xs text-gray-600">
                      Driver: <strong>{d.drivers?.profiles?.full_name ?? '—'}</strong>
                    </p>
                    {d.attempted_at && (
                      <p className="text-xs text-gray-500 mt-0.5">
                        Attempted: {formatDateTime(d.attempted_at)}
                      </p>
                    )}
                    {d.failure_reason && (
                      <p className="text-xs text-red-600 mt-1">⚠ {d.failure_reason}</p>
                    )}
                    {d.reschedule_date && (
                      <p className="text-xs text-blue-600 mt-0.5">
                        Rescheduled for: {formatDate(d.reschedule_date)}
                      </p>
                    )}
                    {/* POD */}
                    {d.pod_records?.length > 0 && (
                      <div className="mt-2 pt-2 border-t border-gray-200">
                        <p className="text-xs font-medium text-green-700 flex items-center gap-1">
                          <CheckCircle2 size={12} /> POD captured
                        </p>
                        <p className="text-xs text-gray-600 mt-0.5">
                          Signed by: {d.pod_records[0].recipient_name}
                        </p>
                      </div>
                    )}
                  </div>
                ))}
              </CardBody>
            </Card>
          )}

          {/* Status History Timeline */}
          {history.length > 0 && (
            <Card>
              <CardHeader><CardTitle>Status History</CardTitle></CardHeader>
              <CardBody>
                <div className="relative">
                  <div className="absolute left-3 top-0 bottom-0 w-0.5 bg-gray-100" />
                  <div className="space-y-4">
                    {history.map((h: any, i: number) => (
                      <div key={h.id} className="flex gap-4 relative">
                        <div className="w-6 h-6 rounded-full bg-white border-2 border-blue-400 shrink-0 z-10 flex items-center justify-center">
                          <div className="w-2 h-2 rounded-full bg-blue-400" />
                        </div>
                        <div className="flex-1 pb-1">
                          <div className="flex items-center gap-2">
                            <span className="text-sm font-medium text-gray-900 capitalize">
                              {h.new_status.replace(/_/g, ' ')}
                            </span>
                            {h.old_status && (
                              <span className="text-xs text-gray-400">
                                ← {h.old_status.replace(/_/g, ' ')}
                              </span>
                            )}
                          </div>
                          <p className="text-xs text-gray-500 mt-0.5">
                            {formatDateTime(h.created_at)}
                            {h.profiles?.full_name && ` · ${h.profiles.full_name}`}
                          </p>
                          {h.notes && <p className="text-xs text-gray-600 mt-1 italic">{h.notes}</p>}
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </CardBody>
            </Card>
          )}
        </div>

        {/* ── Sidebar ─────────────────────────────────────────── */}
        <div className="space-y-4">
          {/* Actions panel */}
          <Card>
            <CardHeader><CardTitle>Order Actions</CardTitle></CardHeader>
            <CardBody className="space-y-4">
              <StatusActions
                orderId={order.id}
                currentStatus={order.status as OrderStatus}
              />

              {/* Failed delivery button — only when a delivery record exists */}
              {latestDelivery && ['dispatched','in_transit'].includes(order.status) && (
                <div className="pt-2 border-t border-gray-100">
                  <OrderDetailFailedButton
                    orderId={order.id}
                    deliveryId={latestDelivery.id}
                  />
                </div>
              )}
            </CardBody>
          </Card>

          {/* Client */}
          {order.clients_3pl && (
            <Card>
              <CardHeader>
                <CardTitle>Client</CardTitle>
                <Link href={`/dashboard/clients/${order.clients_3pl.id}`} className="text-xs text-blue-600 hover:underline">
                  View →
                </Link>
              </CardHeader>
              <CardBody className="space-y-2">
                <div className="flex items-start gap-2">
                  <Building2 size={15} className="text-gray-400 mt-0.5 shrink-0" />
                  <div>
                    <p className="font-semibold text-gray-900">{order.clients_3pl.company_name}</p>
                    <span className="text-xs bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded capitalize">
                      {order.clients_3pl.tier}
                    </span>
                  </div>
                </div>
                <p className="text-sm text-gray-700">{order.clients_3pl.contact_name}</p>
                <p className="text-sm text-blue-600">{order.clients_3pl.contact_email}</p>
                <p className="text-sm text-gray-600">{order.clients_3pl.contact_phone}</p>
              </CardBody>
            </Card>
          )}

          {/* Quick info */}
          <Card>
            <CardBody className="space-y-3 text-sm">
              <div className="flex justify-between">
                <span className="text-gray-500">Order ID</span>
                <span className="font-mono text-xs text-gray-600 truncate ml-2" title={order.id}>
                  {order.id.slice(0, 8)}…
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500">Priority</span>
                <span className="font-medium text-gray-800 capitalize">{order.priority}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500">Deliveries</span>
                <span className="font-medium text-gray-800">{deliveries.length}</span>
              </div>
              {order.confirmed_at && (
                <div className="flex justify-between">
                  <span className="text-gray-500">Confirmed</span>
                  <span className="text-xs text-gray-600">{formatDate(order.confirmed_at)}</span>
                </div>
              )}
              {order.dispatched_at && (
                <div className="flex justify-between">
                  <span className="text-gray-500">Dispatched</span>
                  <span className="text-xs text-gray-600">{formatDate(order.dispatched_at)}</span>
                </div>
              )}
              {order.delivered_at && (
                <div className="flex justify-between">
                  <span className="text-gray-500">Delivered</span>
                  <span className="text-xs text-green-700 font-medium">{formatDate(order.delivered_at)}</span>
                </div>
              )}
            </CardBody>
          </Card>
        </div>
      </div>
    </div>
  )
}
