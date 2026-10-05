import { createServerClient } from '@/lib/supabase/server'
import { Card, CardHeader, CardTitle, CardBody } from '@/components/ui/card'
import { StatusBadge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { SLAChip } from '@/components/orders/sla-indicator'
import { formatDate } from '@/lib/utils'
import Link from 'next/link'
import { Plus, Filter, AlertTriangle } from 'lucide-react'

export const metadata = { title: 'Orders — 3PL Hub' }

const STATUS_TABS = [
  { value: '',           label: 'All' },
  { value: 'pending',    label: 'Pending' },
  { value: 'confirmed',  label: 'Confirmed' },
  { value: 'dispatched', label: 'Dispatched' },
  { value: 'in_transit', label: 'In Transit' },
  { value: 'delivered',  label: 'Delivered' },
  { value: 'failed',     label: 'Failed' },
]

interface PageProps {
  searchParams: { status?: string; q?: string }
}

export default async function OrdersPage({ searchParams }: PageProps) {
  const supabase = createServerClient()
  const statusFilter = searchParams.status ?? ''

  let query = supabase
    .from('orders')
    .select(`
      id, order_number, status, total_items, total_weight_kg,
      delivery_address, delivery_date, sla_hours, created_at,
      clients_3pl(id, company_name)
    `, { count: 'exact' })
    .order('created_at', { ascending: false })
    .limit(50)

  if (statusFilter) query = query.eq('status', statusFilter)
  if (searchParams.q) {
    query = query.or(`order_number.ilike.%${searchParams.q}%,delivery_address.ilike.%${searchParams.q}%`)
  }

  const { data: orders, count } = await query

  // Counts per status for tab badges
  const { data: statusCounts } = await supabase
    .from('orders')
    .select('status')

  const counts: Record<string, number> = {}
  statusCounts?.forEach(r => {
    counts[r.status] = (counts[r.status] ?? 0) + 1
  })

  return (
    <div className="space-y-5">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Order Management</h1>
          <p className="text-gray-500 text-sm mt-0.5">
            {count ?? 0} orders · Module 01
          </p>
        </div>
        <Link href="/dashboard/orders/new">
          <Button size="sm"><Plus size={14} /> New Order</Button>
        </Link>
      </div>

      {/* Status tabs */}
      <div className="flex gap-1 flex-wrap">
        {STATUS_TABS.map(tab => {
          const isActive = statusFilter === tab.value
          const tabCount = tab.value ? (counts[tab.value] ?? 0) : Object.values(counts).reduce((a, b) => a + b, 0)
          return (
            <Link
              key={tab.value}
              href={tab.value ? `/dashboard/orders?status=${tab.value}` : '/dashboard/orders'}
              className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors ${
                isActive
                  ? 'bg-blue-600 text-white'
                  : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'
              }`}
            >
              {tab.label}
              {tabCount > 0 && (
                <span className={`text-xs px-1.5 py-0.5 rounded-full ${isActive ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500'}`}>
                  {tabCount}
                </span>
              )}
            </Link>
          )
        })}
      </div>

      {/* Table */}
      <Card>
        <CardBody className="p-0">
          {orders && orders.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="w-full text-sm min-w-[800px]">
                <thead>
                  <tr className="border-b border-gray-100 bg-gray-50">
                    <th className="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase">Order #</th>
                    <th className="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase">Client</th>
                    <th className="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase">Destination</th>
                    <th className="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase">Items</th>
                    <th className="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase">SLA Status</th>
                    <th className="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th className="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase">Created</th>
                    <th className="px-5 py-3" />
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {orders.map((order: any) => (
                    <tr key={order.id} className="hover:bg-gray-50 transition-colors">
                      <td className="px-5 py-3.5 font-mono text-xs text-blue-600 font-semibold">
                        {order.order_number}
                      </td>
                      <td className="px-5 py-3.5 text-gray-700">
                        {order.clients_3pl?.company_name ?? <span className="text-gray-400 text-xs">No client</span>}
                      </td>
                      <td className="px-5 py-3.5 text-gray-600 max-w-[160px] truncate" title={order.delivery_address}>
                        {order.delivery_address}
                      </td>
                      <td className="px-5 py-3.5 text-gray-700 tabular-nums">
                        {order.total_items}
                        {order.total_weight_kg && <span className="text-gray-400 text-xs ml-1">/ {order.total_weight_kg}kg</span>}
                      </td>
                      <td className="px-5 py-3.5">
                        <SLAChip
                          createdAt={order.created_at}
                          slaHours={order.sla_hours}
                          status={order.status}
                        />
                      </td>
                      <td className="px-5 py-3.5"><StatusBadge status={order.status} /></td>
                      <td className="px-5 py-3.5 text-gray-500 text-xs">{formatDate(order.created_at)}</td>
                      <td className="px-5 py-3.5">
                        <Link href={`/dashboard/orders/${order.id}`} className="text-xs text-blue-600 hover:underline font-medium">
                          View →
                        </Link>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="py-16 text-center">
              <p className="text-gray-400 text-sm mb-3">
                {statusFilter ? `No ${statusFilter} orders` : 'No orders yet'}
              </p>
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
