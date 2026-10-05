import { createServerClient } from '@/lib/supabase/server'
import { Card, CardHeader, CardTitle, CardBody } from '@/components/ui/card'
import { StatusBadge, Chip } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { formatDate } from '@/lib/utils'
import Link from 'next/link'
import { Plus, Users } from 'lucide-react'

export const metadata = { title: '3PL Clients — 3PL Hub' }

const tierColors: Record<string, 'gray' | 'blue' | 'yellow' | 'purple'> = {
  standard: 'gray',
  silver: 'blue',
  gold: 'yellow',
  platinum: 'purple',
}

export default async function ClientsPage() {
  const supabase = createServerClient()

  const { data: clients, count } = await supabase
    .from('clients_3pl')
    .select('*', { count: 'exact' })
    .order('created_at', { ascending: false })
    .limit(50)

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">3PL Client Portal</h1>
          <p className="text-gray-500 text-sm mt-1">
            {count ?? 0} clients onboarded · Module 05
          </p>
        </div>
        <Button size="sm"><Plus size={14} /> Onboard Client</Button>
      </div>

      {/* KYC Stats */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
        {['pending', 'in_review', 'approved', 'rejected'].map(s => (
          <div key={s} className="bg-white rounded-xl border border-gray-200 p-4">
            <p className="text-2xl font-bold text-gray-900">
              {clients?.filter(c => c.kyc_status === s).length ?? 0}
            </p>
            <p className="text-sm text-gray-500 mt-1 capitalize">{s.replace(/_/g, ' ')}</p>
          </div>
        ))}
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Client Directory</CardTitle>
        </CardHeader>
        <CardBody className="p-0">
          {clients && clients.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="w-full text-sm min-w-[700px]">
                <thead>
                  <tr className="border-b border-gray-100 bg-gray-50">
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Company</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Contact</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Industry</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Tier</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">KYC</th>
                    <th className="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Since</th>
                    <th className="px-6 py-3" />
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {clients.map((c: any) => (
                    <tr key={c.id} className="hover:bg-gray-50">
                      <td className="px-6 py-3 font-semibold text-gray-900">{c.company_name}</td>
                      <td className="px-6 py-3">
                        <p className="text-gray-700">{c.contact_name}</p>
                        <p className="text-xs text-gray-500">{c.contact_email}</p>
                      </td>
                      <td className="px-6 py-3 text-gray-600">{c.industry ?? '—'}</td>
                      <td className="px-6 py-3">
                        <Chip label={c.tier} color={tierColors[c.tier] ?? 'gray'} />
                      </td>
                      <td className="px-6 py-3"><StatusBadge status={c.kyc_status} /></td>
                      <td className="px-6 py-3 text-gray-500">{formatDate(c.created_at)}</td>
                      <td className="px-6 py-3">
                        <Link href={`/dashboard/clients/${c.id}`} className="text-xs text-blue-600 hover:underline">
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
              <Users size={32} className="mx-auto text-gray-300 mb-3" />
              <p className="text-gray-400 text-sm">No clients onboarded yet</p>
            </div>
          )}
        </CardBody>
      </Card>
    </div>
  )
}
