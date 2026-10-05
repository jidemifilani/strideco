import { createServerClient } from '@/lib/supabase/server'
import { Card, CardHeader, CardTitle, CardBody } from '@/components/ui/card'
import { StatusBadge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { formatDate, formatCurrency } from '@/lib/utils'
import Link from 'next/link'
import { Receipt, Plus } from 'lucide-react'

export const metadata = { title: 'Billing & Revenue — 3PL Hub' }

export default async function BillingPage() {
  const supabase = createServerClient()

  const [invoicesRes, rateCardsRes] = await Promise.all([
    supabase
      .from('invoices')
      .select(`*, clients_3pl(company_name)`, { count: 'exact' })
      .order('created_at', { ascending: false })
      .limit(30),
    supabase.from('rate_cards').select('*').limit(20),
  ])

  const invoices = invoicesRes.data ?? []
  const ratecards = rateCardsRes.data ?? []

  const totalRevenue = invoices
    .filter(i => i.status === 'paid')
    .reduce((sum: number, i: any) => sum + (i.total_amount ?? 0), 0)

  const outstanding = invoices
    .filter(i => ['issued', 'overdue'].includes(i.status))
    .reduce((sum: number, i: any) => sum + (i.total_amount ?? 0), 0)

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Billing & Revenue</h1>
          <p className="text-gray-500 text-sm mt-1">
            {invoicesRes.count ?? 0} invoices · Module 06
          </p>
        </div>
        <Button size="sm"><Plus size={14} /> Generate Invoice</Button>
      </div>

      {/* Revenue Summary */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div className="bg-white rounded-xl border border-gray-200 p-4">
          <p className="text-2xl font-bold text-green-700">{formatCurrency(totalRevenue)}</p>
          <p className="text-sm text-gray-500 mt-1">Total Collected</p>
        </div>
        <div className="bg-white rounded-xl border border-gray-200 p-4">
          <p className="text-2xl font-bold text-orange-600">{formatCurrency(outstanding)}</p>
          <p className="text-sm text-gray-500 mt-1">Outstanding</p>
        </div>
        <div className="bg-white rounded-xl border border-gray-200 p-4">
          <p className="text-2xl font-bold text-gray-900">
            {invoices.filter(i => i.status === 'overdue').length}
          </p>
          <p className="text-sm text-gray-500 mt-1">Overdue</p>
        </div>
        <div className="bg-white rounded-xl border border-gray-200 p-4">
          <p className="text-2xl font-bold text-gray-900">
            {invoices.filter(i => i.status === 'disputed').length}
          </p>
          <p className="text-sm text-gray-500 mt-1">Disputes</p>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Invoices Table */}
        <div className="lg:col-span-2">
          <Card>
            <CardHeader>
              <CardTitle>Invoices</CardTitle>
            </CardHeader>
            <CardBody className="p-0">
              {invoices.length > 0 ? (
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b border-gray-100 bg-gray-50">
                        <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Invoice #</th>
                        <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Client</th>
                        <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Due</th>
                        <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-50">
                      {invoices.map((inv: any) => (
                        <tr key={inv.id} className="hover:bg-gray-50">
                          <td className="px-4 py-3 font-mono text-xs text-blue-600">{inv.invoice_number}</td>
                          <td className="px-4 py-3 text-gray-700">{inv.clients_3pl?.company_name ?? '—'}</td>
                          <td className="px-4 py-3 font-medium text-gray-900">{formatCurrency(inv.total_amount)}</td>
                          <td className="px-4 py-3 text-gray-500">{inv.due_date ? formatDate(inv.due_date) : '—'}</td>
                          <td className="px-4 py-3"><StatusBadge status={inv.status} /></td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="py-12 text-center">
                  <Receipt size={28} className="mx-auto text-gray-300 mb-2" />
                  <p className="text-gray-400 text-sm">No invoices generated yet</p>
                </div>
              )}
            </CardBody>
          </Card>
        </div>

        {/* Rate Cards */}
        <Card>
          <CardHeader>
            <CardTitle>Rate Cards</CardTitle>
          </CardHeader>
          <CardBody className="space-y-3">
            {ratecards.length > 0 ? ratecards.map((r: any) => (
              <div key={r.id} className="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div>
                  <p className="text-sm font-medium text-gray-900 capitalize">{r.rate_type.replace(/_/g, ' ')}</p>
                  {r.zone && <p className="text-xs text-gray-500">{r.zone}</p>}
                </div>
                <p className="font-semibold text-gray-900">{formatCurrency(r.rate_value)}</p>
              </div>
            )) : (
              <p className="text-sm text-gray-400 text-center py-4">No rate cards configured</p>
            )}
          </CardBody>
        </Card>
      </div>
    </div>
  )
}
