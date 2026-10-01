import { createServerClient } from '@/lib/supabase/server'
import { Card, CardHeader, CardTitle, CardBody } from '@/components/ui/card'
import { StatusBadge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { formatDate } from '@/lib/utils'
import { Plus, Truck } from 'lucide-react'

export const metadata = { title: 'Vehicles — Fleet Management' }

export default async function VehiclesPage() {
  const supabase = createServerClient()
  const { data: vehicles } = await supabase
    .from('vehicles')
    .select('*')
    .order('created_at', { ascending: false })

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Vehicle Registry</h1>
          <p className="text-gray-500 text-sm mt-1">{vehicles?.length ?? 0} vehicles registered</p>
        </div>
        <Button size="sm"><Plus size={14} /> Add Vehicle</Button>
      </div>

      <Card>
        <CardHeader><CardTitle>All Vehicles</CardTitle></CardHeader>
        <CardBody className="p-0">
          {vehicles && vehicles.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="w-full text-sm min-w-[800px]">
                <thead>
                  <tr className="border-b border-gray-100 bg-gray-50">
                    {['Registration','Make / Model','Year','Type','Capacity','Next Service','Status'].map(h => (
                      <th key={h} className="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase">{h}</th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {vehicles.map((v: any) => (
                    <tr key={v.id} className="hover:bg-gray-50">
                      <td className="px-5 py-3 font-mono text-xs font-bold text-gray-900">{v.registration}</td>
                      <td className="px-5 py-3 text-gray-700">{v.make} {v.model}</td>
                      <td className="px-5 py-3 text-gray-600">{v.year ?? '—'}</td>
                      <td className="px-5 py-3 capitalize text-gray-600">{v.type}</td>
                      <td className="px-5 py-3 text-gray-600">{v.capacity_kg}kg</td>
                      <td className="px-5 py-3 text-gray-500">
                        {v.next_service_date ? formatDate(v.next_service_date) : '—'}
                      </td>
                      <td className="px-5 py-3"><StatusBadge status={v.status} /></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="py-16 text-center">
              <Truck size={32} className="mx-auto text-gray-300 mb-3" />
              <p className="text-gray-400 text-sm">No vehicles registered</p>
            </div>
          )}
        </CardBody>
      </Card>
    </div>
  )
}
