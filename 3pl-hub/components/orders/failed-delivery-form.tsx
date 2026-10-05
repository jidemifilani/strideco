'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { Button } from '@/components/ui/button'
import { recordFailedDelivery, type FailureReasonCode } from '@/lib/orders/actions'
import { AlertTriangle, X } from 'lucide-react'

interface FailedDeliveryFormProps {
  orderId: string
  deliveryId: string
  onClose: () => void
}

const REASON_CODES: Array<{ value: FailureReasonCode; label: string }> = [
  { value: 'no_one_home',    label: 'No one at home / office' },
  { value: 'wrong_address',  label: 'Wrong or incomplete address' },
  { value: 'refused',        label: 'Recipient refused delivery' },
  { value: 'damaged',        label: 'Goods damaged in transit' },
  { value: 'access_denied',  label: 'Cannot access delivery location' },
  { value: 'other',          label: 'Other reason' },
]

export function FailedDeliveryForm({ orderId, deliveryId, onClose }: FailedDeliveryFormProps) {
  const [reasonCode, setReasonCode] = useState<FailureReasonCode>('no_one_home')
  const [notes, setNotes] = useState('')
  const [reschedule, setReschedule] = useState(false)
  const [rescheduleDate, setRescheduleDate] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const router = useRouter()

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (reschedule && !rescheduleDate) {
      setError('Please select a reschedule date')
      return
    }

    setLoading(true)
    setError('')

    const result = await recordFailedDelivery(
      orderId,
      deliveryId,
      reasonCode,
      notes,
      reschedule ? rescheduleDate : undefined
    )

    if (!result.ok) {
      setError(result.error)
      setLoading(false)
      return
    }

    router.refresh()
    onClose()
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
      <div className="bg-white rounded-2xl shadow-xl w-full max-w-md">
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <div className="flex items-center gap-2">
            <AlertTriangle size={18} className="text-red-500" />
            <h2 className="font-semibold text-gray-900">Record Failed Delivery</h2>
          </div>
          <button onClick={onClose} className="p-1.5 hover:bg-gray-100 rounded-lg">
            <X size={16} className="text-gray-500" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          {error && (
            <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
              {error}
            </div>
          )}

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1.5">
              Failure reason *
            </label>
            <div className="space-y-1.5">
              {REASON_CODES.map(rc => (
                <label key={rc.value} className="flex items-center gap-2.5 p-2.5 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors">
                  <input
                    type="radio"
                    name="reason"
                    value={rc.value}
                    checked={reasonCode === rc.value}
                    onChange={() => setReasonCode(rc.value)}
                    className="text-blue-600"
                  />
                  <span className="text-sm text-gray-700">{rc.label}</span>
                </label>
              ))}
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1.5">
              Notes / details
            </label>
            <textarea
              value={notes}
              onChange={e => setNotes(e.target.value)}
              rows={3}
              placeholder="Additional details about the failed delivery attempt…"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div className="p-3 bg-gray-50 rounded-xl border border-gray-200 space-y-3">
            <label className="flex items-center gap-2.5 cursor-pointer">
              <input
                type="checkbox"
                checked={reschedule}
                onChange={e => setReschedule(e.target.checked)}
                className="rounded text-blue-600"
              />
              <span className="text-sm font-medium text-gray-700">Schedule re-delivery</span>
            </label>

            {reschedule && (
              <div>
                <label className="block text-xs text-gray-500 mb-1">Reschedule date *</label>
                <input
                  type="date"
                  value={rescheduleDate}
                  onChange={e => setRescheduleDate(e.target.value)}
                  min={new Date().toISOString().slice(0, 10)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            )}
          </div>

          <div className="flex gap-3 pt-1">
            <Button type="button" variant="outline" className="flex-1" onClick={onClose}>
              Cancel
            </Button>
            <Button type="submit" variant="danger" loading={loading} className="flex-1">
              {reschedule ? 'Record & Reschedule' : 'Mark as Failed'}
            </Button>
          </div>
        </form>
      </div>
    </div>
  )
}
