'use client'

import { useState, useEffect, useRef } from 'react'
import { createClient } from '@/lib/supabase/client'
import { Search, Building2, ChevronDown } from 'lucide-react'
import { cn } from '@/lib/utils'
import type { Client3PL } from '@/types'

interface ClientSelectorProps {
  value: string
  onChange: (id: string, name: string) => void
  className?: string
}

export function ClientSelector({ value, onChange, className }: ClientSelectorProps) {
  const [open, setOpen] = useState(false)
  const [query, setQuery] = useState('')
  const [clients, setClients] = useState<Client3PL[]>([])
  const [selectedName, setSelectedName] = useState('')
  const [loading, setLoading] = useState(false)
  const ref = useRef<HTMLDivElement>(null)
  const supabase = createClient()

  // Close on outside click
  useEffect(() => {
    function onClickOutside(e: MouseEvent) {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false)
    }
    document.addEventListener('mousedown', onClickOutside)
    return () => document.removeEventListener('mousedown', onClickOutside)
  }, [])

  // Fetch matching clients
  useEffect(() => {
    if (!open) return
    setLoading(true)
    const q = supabase
      .from('clients_3pl')
      .select('id, company_name, contact_name, contact_email, kyc_status, tier')
      .eq('kyc_status', 'approved')
      .order('company_name')
      .limit(20)

    if (query) q.ilike('company_name', `%${query}%`)

    q.then(({ data }) => {
      setClients((data as Client3PL[]) ?? [])
      setLoading(false)
    })
  }, [open, query])

  function select(client: Client3PL) {
    onChange(client.id, client.company_name)
    setSelectedName(client.company_name)
    setOpen(false)
    setQuery('')
  }

  return (
    <div className={cn('relative', className)} ref={ref}>
      <button
        type="button"
        onClick={() => setOpen(v => !v)}
        className={cn(
          'w-full flex items-center justify-between gap-2 px-3 py-2 border rounded-lg text-sm',
          'bg-white focus:outline-none focus:ring-2 focus:ring-blue-500',
          value ? 'border-gray-300 text-gray-900' : 'border-gray-300 text-gray-400'
        )}
      >
        <span className="flex items-center gap-2 truncate">
          <Building2 size={15} className="text-gray-400 shrink-0" />
          {selectedName || (value ? value : 'Select approved client…')}
        </span>
        <ChevronDown size={14} className={cn('text-gray-400 shrink-0 transition-transform', open && 'rotate-180')} />
      </button>

      {open && (
        <div className="absolute z-50 top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">
          <div className="p-2 border-b border-gray-100">
            <div className="flex items-center gap-2 px-2 py-1.5 bg-gray-50 rounded-lg">
              <Search size={13} className="text-gray-400" />
              <input
                autoFocus
                value={query}
                onChange={e => setQuery(e.target.value)}
                placeholder="Search clients…"
                className="bg-transparent text-sm text-gray-700 placeholder-gray-400 flex-1 outline-none"
              />
            </div>
          </div>

          <div className="max-h-56 overflow-y-auto">
            {loading ? (
              <div className="px-4 py-3 text-sm text-gray-400">Loading…</div>
            ) : clients.length === 0 ? (
              <div className="px-4 py-3 text-sm text-gray-400">
                {query ? 'No approved clients match' : 'No approved clients yet'}
              </div>
            ) : (
              clients.map(c => (
                <button
                  key={c.id}
                  type="button"
                  onClick={() => select(c)}
                  className={cn(
                    'w-full flex items-start gap-3 px-4 py-2.5 hover:bg-blue-50 text-left transition-colors',
                    value === c.id && 'bg-blue-50'
                  )}
                >
                  <Building2 size={15} className="text-gray-400 shrink-0 mt-0.5" />
                  <div>
                    <p className="text-sm font-medium text-gray-900">{c.company_name}</p>
                    <p className="text-xs text-gray-500">{c.contact_name} · {c.contact_email}</p>
                  </div>
                  <span className="ml-auto text-xs bg-green-50 text-green-700 px-1.5 py-0.5 rounded capitalize shrink-0">
                    {c.tier}
                  </span>
                </button>
              ))
            )}
          </div>

          <div className="p-2 border-t border-gray-100">
            <button
              type="button"
              onClick={() => { onChange('', ''); setSelectedName(''); setOpen(false) }}
              className="w-full text-xs text-gray-500 hover:text-gray-700 py-1 text-center"
            >
              Clear selection
            </button>
          </div>
        </div>
      )}
    </div>
  )
}
