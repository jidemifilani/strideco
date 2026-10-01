import { createServerClient } from '@/lib/supabase/server'
import { StatCard } from '@/components/ui/card'
import { Card, CardHeader, CardTitle, CardBody } from '@/components/ui/card'
import { StatusBadge } from '@/components/ui/badge'
import { formatDate, formatCurrency } from '@/lib/utils'
import {
  ShoppingCart,
  PackageCheck,
  Truck,
  DollarSign,
  TrendingUp,
  Clock,
} from 'lucide-react'

export default async function DashboardPage() {
  const supabase = createServerClient()

  // Parallel data fetches
  const [ordersRes, deliveriesRes, fleetRes] = await Promise.all([
    supabase.from('orders').select('*', { count: 'exact', head: true }),
    supabase.from('deliveries').select('*', { count: 'exact', head: true }).eq('status', 'pending'),
    supabase.from('vehicles').select('*', { count: 'exact', head: true }).eq('status', 'in_use'),
  ])

  const recentOrders = await supabase
    .from('orders')
    .select('id, order_number, status, created_at, clients_3pl(company_name)')
    .order('created_at', { ascending: false })
    .limit(5)

  const stats = {
    orders_total: ordersRes.count ?? 0,
    deliveries_pending: deliveriesRes.count ?? 0,
    fleet_active: fleetRes.count ?? 0,
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Operations Overview</h1>
        <p className="text-gray-500 text-sm mt-1">Real-time snapshot of your last-mile network</p>
      </div>

      {/* KPI Row */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          title="Total Orders"
          value={stats.orders_total}
          sub="All time"
          icon={ShoppingCart}
          trend={8}
          color="blue"
        />
        <StatCard
          title="Pending Deliveries"
          value={stats.deliveries_pending}
          sub="Awaiting dispatch"
          icon={Clock}
          color="yellow"
        />
        <StatCard
          title="Fleet Active"
          value={stats.fleet_active}
          sub="Vehicles on road"
          icon={Truck}
          color="green"
        />
        <StatCard
          title="Revenue (MTD)"
          value={formatCurrency(0)}
          sub="Month to date"
          icon={DollarSign}
          trend={12}
          color="purple"
        />
      </div>

      {/* Recent Orders */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Recent Orders</CardTitle>
            <a href="/dashboard/orders" className="text-sm text-blue-600 hover:underline">
              View all →
            </a>
          </CardHeader>
          <CardBody className="p-0">
            {recentOrders.data && recentOrders.data.length > 0 ? (
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-gray-100 bg-gray-50">
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {recentOrders.data.map((order: any) => (
                    <tr key={order.id} className="hover:bg-gray-50 transition-colors">
                      <td className="px-6 py-3 font-mono text-xs text-blue-600">{order.order_number}</td>
                      <td className="px-6 py-3 text-gray-700">{order.clients_3pl?.company_name ?? '—'}</td>
                      <td className="px-6 py-3"><StatusBadge status={order.status} /></td>
                      <td className="px-6 py-3 text-gray-500">{formatDate(order.created_at)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            ) : (
              <div className="px-6 py-10 text-center text-gray-400 text-sm">
                No orders yet. <a href="/dashboard/orders/new" className="text-blue-600 hover:underline">Create the first order →</a>
              </div>
            )}
          </CardBody>
        </Card>

        {/* Module Status */}
        <Card>
          <CardHeader>
            <CardTitle>Module Status</CardTitle>
          </CardHeader>
          <CardBody className="space-y-3">
            {[
              { name: 'Order Management', module: 'M01', status: 'active' },
              { name: 'Fleet Management', module: 'M02', status: 'active' },
              { name: 'Route Optimisation', module: 'M03', status: 'active' },
              { name: 'Delivery & POD', module: 'M04', status: 'active' },
              { name: '3PL Client Portal', module: 'M05', status: 'active' },
              { name: 'Billing & Revenue', module: 'M06', status: 'active' },
            ].map(m => (
              <div key={m.module} className="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div className="flex items-center gap-3">
                  <span className="text-xs bg-blue-50 text-blue-600 font-mono px-2 py-0.5 rounded">{m.module}</span>
                  <span className="text-sm text-gray-700">{m.name}</span>
                </div>
                <span className="flex items-center gap-1.5 text-xs text-green-600">
                  <span className="w-1.5 h-1.5 bg-green-500 rounded-full" />
                  Live
                </span>
              </div>
            ))}
          </CardBody>
        </Card>
      </div>
    </div>
  )
}
