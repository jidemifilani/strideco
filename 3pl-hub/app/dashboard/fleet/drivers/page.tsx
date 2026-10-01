import { createServerClient } from '@/lib/supabase/server'
import { Card, CardHeader, CardTitle, CardBody } from '@/components/ui/card'
import { StatusBadge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { formatDate } from '@/lib/utils'
import { Plus, User } from 'lucide-react'

export const metadata = { title: 'Drivers — Fleet Management' }

export default async function DriversPage() {
  const supabase = createServerClient()
  const { data: drivers } = await supabase
    .from('drivers')
    .select('*, profiles(full_name, email, phone), vehicles(registration, type)')
    .order('created_at', { ascending: false })

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Driver Management</h1>
          <p className="text-gray-500 text-sm mt-1">{drivers?.length ?? 0} drivers registered</p>
        </div>
        <Button size="sm"><Plus size={14} /> Add Driver</Button>
      </div>

      <Card>
        <CardHeader><CardTitle>All Drivers</CardTitle></CardHeader>
        <CardBody className="p-0">
          {drivers && drivers.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="w-full text-sm min-w-[900px]">
                <thead>
                  <tr className="border-b border-gray-100 bg-gray-50">
                    {['Name','Licence #','Licence Expiry','Assigned Vehicle','Deliveries','KPI Score','Status'].map(h => (
                      <th key={h} className="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase">{h}</th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {drivers.map((d: any) => (
                    <tr key={d.id} className="hover:bg-gray-50">
                      <td className="px-5 py-3">
                        <p className="font-medium text-gray-900">{d.profiles?.full_name ?? '—'}</p>
                        <p className="text-xs text-gray-500">{d.profiles?.email}</p>
                      </td>
                      <td className="px-5 py-3 font-mono text-xs text-gray-700">{d.license_number}</td>
                      <td className="px-5 py-3 text-gray-600">{d.license_expiry ? formatDate(d.license_expiry) : '—'}</td>
                      <td className="px-5 py-3 text-gray-600">
                        {d.vehicles ? `${d.vehicles.registration} (${d.vehicles.type})` : '—'}
                      </td>
                      <td className="px-5 py-3 text-gray-700">{d.total_deliveries ?? 0}</td>
                      <td className="px-5 py-3">
                        <span className={`font-semibold ${d.kpi_score >= 80 ? 'text-green-600' : d.kpi_score >= 60 ? 'text-yellow-600' : 'text-red-600'}`}>
                          {d.kpi_score?.toFixed(1) ?? '0.0'}
                        </span>
                      </td>
                      <td className="px-5 py-3"><StatusBadge status={d.status} /></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="py-16 text-center">
              <User size={32} className="mx-auto text-gray-300 mb-3" />
              <p className="text-gray-400 text-sm">No drivers registered</p>
            </div>
          )}
        </CardBody>
      </Card>
    </div>
  )
}
