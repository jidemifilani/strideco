import { createServerClient } from '@/lib/supabase/server'
import { Card, CardHeader, CardTitle, CardBody } from '@/components/ui/card'
import { StatusBadge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { formatDate } from '@/lib/utils'
import Link from 'next/link'
import { Plus, Truck, User } from 'lucide-react'

export const metadata = { title: 'Fleet Management — 3PL Hub' }

export default async function FleetPage() {
  const supabase = createServerClient()

  const [vehiclesRes, driversRes] = await Promise.all([
    supabase.from('vehicles').select('*').order('created_at', { ascending: false }).limit(20),
    supabase.from('drivers').select(`*, profiles(full_name, email)`).order('created_at', { ascending: false }).limit(20),
  ])

  const vehicles = vehiclesRes.data ?? []
  const drivers = driversRes.data ?? []

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Fleet Management</h1>
          <p className="text-gray-500 text-sm mt-1">
            {vehicles.length} vehicles · {drivers.length} drivers · Module 02
          </p>
        </div>
        <div className="flex gap-2">
          <Link href="/dashboard/fleet/vehicles">
            <Button variant="outline" size="sm"><Truck size={14} /> Vehicles</Button>
          </Link>
          <Link href="/dashboard/fleet/drivers">
            <Button variant="outline" size="sm"><User size={14} /> Drivers</Button>
          </Link>
        </div>
      </div>

      {/* Fleet stats */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
        {[
          { label: 'Available', status: 'available', color: 'green' },
          { label: 'In Use',    status: 'in_use',    color: 'blue' },
          { label: 'Maintenance', status: 'maintenance', color: 'yellow' },
          { label: 'Retired',   status: 'retired',   color: 'gray' },
        ].map(s => (
          <div key={s.status} className="bg-white rounded-xl border border-gray-200 p-4">
            <p className="text-2xl font-bold text-gray-900">
              {vehicles.filter(v => v.status === s.status).length}
            </p>
            <p className="text-sm text-gray-500 mt-1">{s.label}</p>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Vehicles */}
        <Card>
          <CardHeader>
            <CardTitle>Vehicles</CardTitle>
            <Link href="/dashboard/fleet/vehicles" className="text-sm text-blue-600 hover:underline">Manage →</Link>
          </CardHeader>
          <CardBody className="p-0">
            {vehicles.length > 0 ? (
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-gray-100 bg-gray-50">
                    <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Reg</th>
                    <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Capacity</th>
                    <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {vehicles.map((v: any) => (
                    <tr key={v.id} className="hover:bg-gray-50">
                      <td className="px-4 py-3 font-mono text-xs font-medium text-gray-900">{v.registration}</td>
                      <td className="px-4 py-3 capitalize text-gray-600">{v.type}</td>
                      <td className="px-4 py-3 text-gray-600">{v.capacity_kg}kg</td>
                      <td className="px-4 py-3"><StatusBadge status={v.status} /></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            ) : (
              <div className="py-12 text-center text-gray-400 text-sm">No vehicles registered yet</div>
            )}
          </CardBody>
        </Card>

        {/* Drivers */}
        <Card>
          <CardHeader>
            <CardTitle>Drivers</CardTitle>
            <Link href="/dashboard/fleet/drivers" className="text-sm text-blue-600 hover:underline">Manage →</Link>
          </CardHeader>
          <CardBody className="p-0">
            {drivers.length > 0 ? (
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-gray-100 bg-gray-50">
                    <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Licence Exp</th>
                    <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">KPI</th>
                    <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {drivers.map((d: any) => (
                    <tr key={d.id} className="hover:bg-gray-50">
                      <td className="px-4 py-3 text-gray-900">{d.profiles?.full_name ?? '—'}</td>
                      <td className="px-4 py-3 text-gray-600">{d.license_expiry ? formatDate(d.license_expiry) : '—'}</td>
                      <td className="px-4 py-3 text-gray-700 font-medium">{d.kpi_score?.toFixed(1) ?? '—'}</td>
                      <td className="px-4 py-3"><StatusBadge status={d.status} /></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            ) : (
              <div className="py-12 text-center text-gray-400 text-sm">No drivers registered yet</div>
            )}
          </CardBody>
        </Card>
      </div>
    </div>
  )
}
