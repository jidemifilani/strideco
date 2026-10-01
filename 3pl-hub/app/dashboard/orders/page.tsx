import { createServerClient } from '@/lib/supabase/server'
import { Card, CardHeader, CardTitle, CardBody } from '@/components/ui/card'
import { StatusBadge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { formatDate } from '@/lib/utils'
import Link from 'next/link'
import { Plus, Filter } from 'lucide-react'

export const metadata = { title: 'Orders — 3PL Hub' }

export default async function OrdersPage() {
  const supabase = createServerClient()

  const { data: orders, count } = await supabase
    .from('orders')
    .select(`
      id, order_number, status, total_items, delivery_address,
      delivery_date, sla_hours, created_at,
      clients_3pl(company_name)
    `, { count: 'exact' })
    .order('created_at', { ascending: false })
    .limit(50)

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Order Management</h1>
          <p className="text-gray-500 text-sm mt-1">
            {count ?? 0} total orders · Module 01
          </p>
        </div>
        <div className="flex items-center gap-2">
          <Button variant="outline" size="sm">
            <Filter size={14} /> Filter
          </Button>
          <Link href="/dashboard/orders/new">
            <Button size="sm">
              <Plus size={14} /> New Order
            </Button>
          </Link>
        </div>
      </div>

      {/* Stats row */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
        {[
          { label: 'Pending',    status: 'pending',    color: 'yellow' },
          { label: 'In Transit', status: 'in_transit', color: 'purple' },
          { label: 'Delivered',  status: 'delivered',  color: 'green' },
          { label: 'Failed',     status: 'failed',     color: 'red' },
        ].map(s => (
          <div key={s.status} className="bg-white rounded-xl border border-gray-200 p-4">
            <p className="text-2xl font-bold text-gray-900">
              {orders?.filter(o => o.status === s.status).length ?? 0}
            </p>
            <p className="text-sm text-gray-500 mt-1">{s.label}</p>
          </div>
        ))}
      </div>

      <Card>
        <CardHeader>
          <CardTitle>All Orders</CardTitle>
        </CardHeader>
        <CardBody className="p-0">
          {orders && orders.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="w-full text-sm min-w-[700px]">
                <thead>
                  <tr className="border-b border-gray-100 bg-gray-50">
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Order #</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Client</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Destination</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Items</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">SLA</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th className="px-6 py-3" />
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {orders.map((order: any) => (
                    <tr key={order.id} className="hover:bg-gray-50 transition-colors">
                      <td className="px-6 py-3 font-mono text-xs text-blue-600 font-medium">
                        {order.order_number}
                      </td>
                      <td className="px-6 py-3 text-gray-700">{order.clients_3pl?.company_name ?? '—'}</td>
                      <td className="px-6 py-3 text-gray-600 max-w-[180px] truncate">{order.delivery_address}</td>
                      <td className="px-6 py-3 text-gray-700">{order.total_items}</td>
                      <td className="px-6 py-3 text-gray-600">{order.sla_hours}h</td>
                      <td className="px-6 py-3"><StatusBadge status={order.status} /></td>
                      <td className="px-6 py-3 text-gray-500">{formatDate(order.created_at)}</td>
                      <td className="px-6 py-3">
                        <Link href={`/dashboard/orders/${order.id}`} className="text-xs text-blue-600 hover:underline">
                          View
                        </Link>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="py-16 text-center">
              <p className="text-gray-400 text-sm mb-3">No orders yet</p>
              <Link href="/dashboard/orders/new">
                <Button size="sm"><Plus size={14} /> Create First Order</Button>
              </Link>
            </div>
          )}
        </CardBody>
      </Card>
    </div>
  )
}
