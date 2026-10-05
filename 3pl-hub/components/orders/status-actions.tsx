'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { Button } from '@/components/ui/button'
import { transitionOrderStatus } from '@/lib/orders/actions'
import type { OrderStatus } from '@/types'
import {
  CheckCircle,
  Send,
  Truck,
  PackageCheck,
  XCircle,
  RotateCcw,
  ChevronDown,
} from 'lucide-react'

const ACTIONS: Record<OrderStatus, Array<{ label: string; next: OrderStatus; icon: React.ReactNode; variant: 'primary' | 'secondary' | 'danger' | 'outline' }>> = {
  pending: [
    { label: 'Confirm Order',  next: 'confirmed',  icon: <CheckCircle size={14} />, variant: 'primary' },
    { label: 'Cancel',         next: 'cancelled',  icon: <XCircle size={14} />,     variant: 'danger' },
  ],
  confirmed: [
    { label: 'Mark Dispatched', next: 'dispatched', icon: <Send size={14} />,        variant: 'primary' },
    { label: 'Cancel',          next: 'cancelled',  icon: <XCircle size={14} />,     variant: 'danger' },
  ],
  dispatched: [
    { label: 'Mark In Transit', next: 'in_transit', icon: <Truck size={14} />,       variant: 'primary' },
    { label: 'Mark Failed',     next: 'failed',     icon: <XCircle size={14} />,     variant: 'outline' },
  ],
  in_transit: [
    { label: 'Mark Delivered',  next: 'delivered',  icon: <PackageCheck size={14} />, variant: 'primary' },
    { label: 'Mark Failed',     next: 'failed',     icon: <XCircle size={14} />,      variant: 'outline' },
    { label: 'Mark Returned',   next: 'returned',   icon: <RotateCcw size={14} />,    variant: 'secondary' },
  ],
  failed: [
    { label: 'Retry Delivery',  next: 'in_transit', icon: <Truck size={14} />,        variant: 'primary' },
    { label: 'Mark Returned',   next: 'returned',   icon: <RotateCcw size={14} />,    variant: 'secondary' },
  ],
  delivered:  [],
  returned:   [],
  cancelled:  [],
}

interface StatusActionsProps {
  orderId: string
  currentStatus: OrderStatus
}

export function StatusActions({ orderId, currentStatus }: StatusActionsProps) {
  const [loading, setLoading] = useState<OrderStatus | null>(null)
  const [error, setError] = useState('')
  const [notes, setNotes] = useState('')
  const [showNotes, setShowNotes] = useState(false)
  const router = useRouter()

  const actions = ACTIONS[currentStatus] ?? []
  if (actions.length === 0) return null

  async function handle(nextStatus: OrderStatus) {
    setLoading(nextStatus)
    setError('')

    const result = await transitionOrderStatus(orderId, nextStatus, notes || undefined)

    if (!result.ok) {
      setError(result.error)
      setLoading(null)
      return
    }

    router.refresh()
    setLoading(null)
    setNotes('')
    setShowNotes(false)
  }

  return (
    <div className="space-y-3">
      {error && (
        <div className="p-2.5 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
          {error}
        </div>
      )}

      <div className="flex flex-col gap-2">
        {actions.map(({ label, next, icon, variant }) => (
          <Button
            key={next}
            variant={variant}
            size="sm"
            loading={loading === next}
            onClick={() => handle(next)}
            className="justify-start gap-2"
          >
            {icon}
            {label}
          </Button>
        ))}
      </div>

      <button
        type="button"
        onClick={() => setShowNotes(v => !v)}
        className="flex items-center gap-1 text-xs text-gray-400 hover:text-gray-600"
      >
        <ChevronDown size={12} className={showNotes ? 'rotate-180' : ''} />
        {showNotes ? 'Hide notes' : 'Add transition notes'}
      </button>

      {showNotes && (
        <textarea
          value={notes}
          onChange={e => setNotes(e.target.value)}
          rows={2}
          placeholder="Optional: reason for this status change…"
          className="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-blue-400"
        />
      )}
    </div>
  )
}
