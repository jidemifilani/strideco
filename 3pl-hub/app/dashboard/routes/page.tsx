import { createServerClient } from '@/lib/supabase/server'
import { Card, CardHeader, CardTitle, CardBody } from '@/components/ui/card'
import { StatusBadge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { formatDate } from '@/lib/utils'
import Link from 'next/link'
import { Plus, Map } from 'lucide-react'

export const metadata = { title: 'Routes — 3PL Hub' }

export default async function RoutesPage() {
  const supabase = createServerClient()

  const { data: routes, count } = await supabase
    .from('routes')
    .select(`
      id, route_name, dispatch_date, status, total_stops,
      estimated_distance_km, estimated_duration_mins, cost_estimate,
      vehicles(registration, type),
      drivers(profiles(full_name))
    `, { count: 'exact' })
    .order('dispatch_date', { ascending: false })
    .limit(30)

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Route Optimisation</h1>
          <p className="text-gray-500 text-sm mt-1">
            {count ?? 0} routes planned · Module 03
          </p>
        </div>
        <Button size="sm">
          <Plus size={14} /> Plan Route
        </Button>
      </div>

      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
        {['planned', 'dispatched', 'in_progress', 'completed'].map(s => (
          <div key={s} className="bg-white rounded-xl border border-gray-200 p-4">
            <p className="text-2xl font-bold text-gray-900">
              {routes?.filter(r => r.status === s).length ?? 0}
            </p>
            <p className="text-sm text-gray-500 mt-1 capitalize">{s.replace(/_/g, ' ')}</p>
          </div>
        ))}
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Active & Planned Routes</CardTitle>
        </CardHeader>
        <CardBody className="p-0">
          {routes && routes.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="w-full text-sm min-w-[700px]">
                <thead>
                  <tr className="border-b border-gray-100 bg-gray-50">
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Route</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Vehicle</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Driver</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Stops</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Distance</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {routes.map((r: any) => (
                    <tr key={r.id} className="hover:bg-gray-50">
                      <td className="px-6 py-3 font-medium text-gray-900">{r.route_name}</td>
                      <td className="px-6 py-3 font-mono text-xs text-gray-600">{r.vehicles?.registration ?? '—'}</td>
                      <td className="px-6 py-3 text-gray-700">{r.drivers?.profiles?.full_name ?? '—'}</td>
                      <td className="px-6 py-3 text-gray-700">{r.total_stops}</td>
                      <td className="px-6 py-3 text-gray-600">{r.estimated_distance_km ? `${r.estimated_distance_km}km` : '—'}</td>
                      <td className="px-6 py-3 text-gray-500">{formatDate(r.dispatch_date)}</td>
                      <td className="px-6 py-3"><StatusBadge status={r.status} /></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="py-16 text-center">
              <Map size={32} className="mx-auto text-gray-300 mb-3" />
              <p className="text-gray-400 text-sm">No routes planned yet</p>
              <p className="text-gray-400 text-xs mt-1">Assign vehicles and drivers to start optimising routes</p>
            </div>
          )}
        </CardBody>
      </Card>
    </div>
  )
}
