import { createServerClient } from '@/lib/supabase/server'
import { Card, CardHeader, CardTitle, CardBody } from '@/components/ui/card'
import { StatusBadge } from '@/components/ui/badge'
import { formatDateTime } from '@/lib/utils'
import Link from 'next/link'
import { PackageCheck, Camera } from 'lucide-react'

export const metadata = { title: 'Deliveries & POD — 3PL Hub' }

export default async function DeliveriesPage() {
  const supabase = createServerClient()

  const { data: deliveries, count } = await supabase
    .from('deliveries')
    .select(`
      id, status, attempt_count, attempted_at, delivered_at, failure_reason,
      orders(order_number, delivery_address, clients_3pl(company_name)),
      drivers(profiles(full_name))
    `, { count: 'exact' })
    .order('attempted_at', { ascending: false })
    .limit(50)

  const podCount = await supabase
    .from('pod_records')
    .select('*', { count: 'exact', head: true })

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Delivery Execution & POD</h1>
          <p className="text-gray-500 text-sm mt-1">
            {count ?? 0} deliveries · {podCount.count ?? 0} POD records · Module 04
          </p>
        </div>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
        {[
          { label: 'Pending',    status: 'pending' },
          { label: 'In Transit', status: 'in_transit' },
          { label: 'Delivered',  status: 'delivered' },
          { label: 'Failed',     status: 'failed' },
        ].map(s => (
          <div key={s.status} className="bg-white rounded-xl border border-gray-200 p-4">
            <p className="text-2xl font-bold text-gray-900">
              {deliveries?.filter(d => d.status === s.status).length ?? 0}
            </p>
            <p className="text-sm text-gray-500 mt-1">{s.label}</p>
          </div>
        ))}
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Delivery Tracker</CardTitle>
        </CardHeader>
        <CardBody className="p-0">
          {deliveries && deliveries.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="w-full text-sm min-w-[800px]">
                <thead>
                  <tr className="border-b border-gray-100 bg-gray-50">
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Order #</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Client</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Driver</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Attempts</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Last Attempt</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">POD</th>
                    <th className="px-6 py-3" />
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {deliveries.map((d: any) => (
                    <tr key={d.id} className="hover:bg-gray-50">
                      <td className="px-6 py-3 font-mono text-xs text-blue-600">{d.orders?.order_number ?? '—'}</td>
                      <td className="px-6 py-3 text-gray-700">{d.orders?.clients_3pl?.company_name ?? '—'}</td>
                      <td className="px-6 py-3 text-gray-600">{d.drivers?.profiles?.full_name ?? '—'}</td>
                      <td className="px-6 py-3 text-center text-gray-700">{d.attempt_count}</td>
                      <td className="px-6 py-3 text-gray-500 text-xs">
                        {d.attempted_at ? formatDateTime(d.attempted_at) : '—'}
                      </td>
                      <td className="px-6 py-3"><StatusBadge status={d.status} /></td>
                      <td className="px-6 py-3">
                        {d.status === 'delivered' ? (
                          <span className="flex items-center gap-1 text-xs text-green-600">
                            <Camera size={12} /> Captured
                          </span>
                        ) : '—'}
                      </td>
                      <td className="px-6 py-3">
                        <Link href={`/dashboard/deliveries/${d.id}`} className="text-xs text-blue-600 hover:underline">
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
              <PackageCheck size={32} className="mx-auto text-gray-300 mb-3" />
              <p className="text-gray-400 text-sm">No deliveries tracked yet</p>
            </div>
          )}
        </CardBody>
      </Card>
    </div>
  )
}
