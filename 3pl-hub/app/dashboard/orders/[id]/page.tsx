import { createServerClient } from '@/lib/supabase/server'
import { Card, CardHeader, CardTitle, CardBody } from '@/components/ui/card'
import { StatusBadge } from '@/components/ui/badge'
import { formatDate, formatDateTime } from '@/lib/utils'
import Link from 'next/link'
import { ArrowLeft, MapPin, Package, Clock } from 'lucide-react'
import { notFound } from 'next/navigation'

export default async function OrderDetailPage({ params }: { params: { id: string } }) {
  const supabase = createServerClient()

  const { data: order } = await supabase
    .from('orders')
    .select(`
      *,
      clients_3pl(company_name, contact_name, contact_email, contact_phone),
      order_items(*)
    `)
    .eq('id', params.id)
    .single()

  if (!order) notFound()

  const { data: deliveries } = await supabase
    .from('deliveries')
    .select('*, drivers(profiles(full_name))')
    .eq('order_id', params.id)

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      <div className="flex items-center gap-3">
        <Link href="/dashboard/orders" className="p-2 hover:bg-gray-100 rounded-lg">
          <ArrowLeft size={18} className="text-gray-600" />
        </Link>
        <div className="flex-1">
          <div className="flex items-center gap-3">
            <h1 className="text-xl font-bold text-gray-900 font-mono">{order.order_number}</h1>
            <StatusBadge status={order.status} />
          </div>
          <p className="text-gray-500 text-sm mt-0.5">Created {formatDateTime(order.created_at)}</p>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Main Details */}
        <div className="lg:col-span-2 space-y-4">
          <Card>
            <CardHeader><CardTitle>Shipment Details</CardTitle></CardHeader>
            <CardBody className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <p className="text-xs font-medium text-gray-500 uppercase mb-1">Pickup</p>
                  <div className="flex items-start gap-2">
                    <MapPin size={14} className="text-blue-500 mt-0.5 shrink-0" />
                    <p className="text-sm text-gray-700">{order.pickup_address}</p>
                  </div>
                </div>
                <div>
                  <p className="text-xs font-medium text-gray-500 uppercase mb-1">Delivery</p>
                  <div className="flex items-start gap-2">
                    <MapPin size={14} className="text-green-500 mt-0.5 shrink-0" />
                    <p className="text-sm text-gray-700">{order.delivery_address}</p>
                  </div>
                </div>
              </div>
              <div className="grid grid-cols-3 gap-4 pt-2 border-t border-gray-100">
                <div>
                  <p className="text-xs text-gray-500 mb-1">Items</p>
                  <div className="flex items-center gap-1.5">
                    <Package size={14} className="text-gray-400" />
                    <p className="text-sm font-medium text-gray-900">{order.total_items}</p>
                  </div>
                </div>
                <div>
                  <p className="text-xs text-gray-500 mb-1">SLA</p>
                  <div className="flex items-center gap-1.5">
                    <Clock size={14} className="text-gray-400" />
                    <p className="text-sm font-medium text-gray-900">{order.sla_hours}h</p>
                  </div>
                </div>
                <div>
                  <p className="text-xs text-gray-500 mb-1">Delivery Date</p>
                  <p className="text-sm font-medium text-gray-900">
                    {order.delivery_date ? formatDate(order.delivery_date) : 'Not set'}
                  </p>
                </div>
              </div>
              {order.notes && (
                <div className="pt-2 border-t border-gray-100">
                  <p className="text-xs text-gray-500 mb-1">Notes</p>
                  <p className="text-sm text-gray-700">{order.notes}</p>
                </div>
              )}
            </CardBody>
          </Card>

          {/* Order Items */}
          {order.order_items && order.order_items.length > 0 && (
            <Card>
              <CardHeader><CardTitle>Items ({order.order_items.length})</CardTitle></CardHeader>
              <CardBody className="p-0">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-gray-100 bg-gray-50">
                      <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Description</th>
                      <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Qty</th>
                      <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Weight</th>
                      <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Barcode</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-50">
                    {order.order_items.map((item: any) => (
                      <tr key={item.id} className="hover:bg-gray-50">
                        <td className="px-4 py-3 text-gray-700">{item.description}</td>
                        <td className="px-4 py-3 text-gray-700">{item.quantity}</td>
                        <td className="px-4 py-3 text-gray-600">{item.weight_kg ? `${item.weight_kg}kg` : '—'}</td>
                        <td className="px-4 py-3 font-mono text-xs text-gray-500">{item.barcode ?? '—'}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </CardBody>
            </Card>
          )}

          {/* Delivery Attempts */}
          {deliveries && deliveries.length > 0 && (
            <Card>
              <CardHeader><CardTitle>Delivery History</CardTitle></CardHeader>
              <CardBody className="space-y-3">
                {deliveries.map((d: any, i: number) => (
                  <div key={d.id} className="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                    <div>
                      <p className="text-sm font-medium text-gray-900">Attempt #{i + 1}</p>
                      <p className="text-xs text-gray-500">
                        Driver: {d.drivers?.profiles?.full_name ?? '—'}
                        {d.attempted_at && ` · ${formatDateTime(d.attempted_at)}`}
                      </p>
                      {d.failure_reason && (
                        <p className="text-xs text-red-600 mt-0.5">{d.failure_reason}</p>
                      )}
                    </div>
                    <StatusBadge status={d.status} />
                  </div>
                ))}
              </CardBody>
            </Card>
          )}
        </div>

        {/* Sidebar */}
        <div className="space-y-4">
          {order.clients_3pl && (
            <Card>
              <CardHeader><CardTitle>Client</CardTitle></CardHeader>
              <CardBody className="space-y-2">
                <p className="font-semibold text-gray-900">{order.clients_3pl.company_name}</p>
                <p className="text-sm text-gray-600">{order.clients_3pl.contact_name}</p>
                <p className="text-sm text-blue-600">{order.clients_3pl.contact_email}</p>
                <p className="text-sm text-gray-600">{order.clients_3pl.contact_phone}</p>
              </CardBody>
            </Card>
          )}

          <Card>
            <CardHeader><CardTitle>Order Actions</CardTitle></CardHeader>
            <CardBody className="space-y-2">
              {order.status === 'pending' && (
                <button className="w-full text-sm text-left px-3 py-2 rounded-lg hover:bg-blue-50 text-blue-700 font-medium transition-colors">
                  Confirm Order
                </button>
              )}
              {order.status === 'confirmed' && (
                <button className="w-full text-sm text-left px-3 py-2 rounded-lg hover:bg-indigo-50 text-indigo-700 font-medium transition-colors">
                  Assign to Route
                </button>
              )}
              <button className="w-full text-sm text-left px-3 py-2 rounded-lg hover:bg-gray-50 text-gray-700 transition-colors">
                Edit Order
              </button>
              {!['delivered','cancelled'].includes(order.status) && (
                <button className="w-full text-sm text-left px-3 py-2 rounded-lg hover:bg-red-50 text-red-600 transition-colors">
                  Cancel Order
                </button>
              )}
            </CardBody>
          </Card>
        </div>
      </div>
    </div>
  )
}
