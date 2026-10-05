'use client'

import { cn } from '@/lib/utils'
import { Clock, AlertTriangle, CheckCircle2 } from 'lucide-react'

interface SLAIndicatorProps {
  createdAt: string
  slaHours: number
  status: string
  className?: string
}

function getSLAState(createdAt: string, slaHours: number, status: string) {
  if (['delivered', 'cancelled', 'returned'].includes(status)) {
    return { pct: 100, breached: false, done: true, label: status === 'delivered' ? 'Delivered' : 'Closed', remaining: '' }
  }

  const created = new Date(createdAt).getTime()
  const deadline = created + slaHours * 3_600_000
  const now = Date.now()
  const elapsed = now - created
  const total = deadline - created
  const pct = Math.min(100, Math.round((elapsed / total) * 100))
  const breached = now > deadline

  const remaining = breached
    ? `Breached ${Math.round((now - deadline) / 3_600_000)}h ago`
    : (() => {
        const ms = deadline - now
        const h = Math.floor(ms / 3_600_000)
        const m = Math.round((ms % 3_600_000) / 60_000)
        return h > 0 ? `${h}h ${m}m left` : `${m}m left`
      })()

  return { pct, breached, done: false, label: remaining, remaining }
}

export function SLAIndicator({ createdAt, slaHours, status, className }: SLAIndicatorProps) {
  const { pct, breached, done, label } = getSLAState(createdAt, slaHours, status)

  const barColor = done
    ? 'bg-green-500'
    : breached
    ? 'bg-red-500'
    : pct > 85
    ? 'bg-orange-500'
    : pct > 50
    ? 'bg-yellow-500'
    : 'bg-green-500'

  const textColor = done
    ? 'text-green-700'
    : breached
    ? 'text-red-700'
    : pct > 85
    ? 'text-orange-700'
    : pct > 50
    ? 'text-yellow-700'
    : 'text-green-700'

  const Icon = done
    ? CheckCircle2
    : breached || pct > 85
    ? AlertTriangle
    : Clock

  return (
    <div className={cn('space-y-1.5', className)}>
      <div className="flex items-center justify-between">
        <div className={cn('flex items-center gap-1.5 text-xs font-medium', textColor)}>
          <Icon size={13} />
          <span>SLA {slaHours}h · {label}</span>
        </div>
        {!done && <span className="text-xs text-gray-400">{pct}%</span>}
      </div>
      <div className="h-1.5 bg-gray-100 rounded-full overflow-hidden">
        <div
          className={cn('h-full rounded-full transition-all', barColor)}
          style={{ width: `${pct}%` }}
        />
      </div>
    </div>
  )
}

// Compact chip version for tables
export function SLAChip({ createdAt, slaHours, status }: { createdAt: string; slaHours: number; status: string }) {
  const { breached, done, label, pct } = getSLAState(createdAt, slaHours, status)

  if (done) return null

  const cls = breached
    ? 'bg-red-50 text-red-700 border-red-200'
    : pct > 85
    ? 'bg-orange-50 text-orange-700 border-orange-200'
    : pct > 50
    ? 'bg-yellow-50 text-yellow-700 border-yellow-200'
    : 'bg-green-50 text-green-700 border-green-200'

  return (
    <span className={cn('inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs border font-medium', cls)}>
      <Clock size={10} />
      {label}
    </span>
  )
}
