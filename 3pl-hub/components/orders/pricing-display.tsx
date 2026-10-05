'use client'

import { formatCurrency } from '@/lib/utils'
import { Tag, Loader2 } from 'lucide-react'
import type { PricingResult } from '@/lib/orders/actions'

interface PricingDisplayProps {
  pricing: PricingResult | null
  loading: boolean
}

export function PricingDisplay({ pricing, loading }: PricingDisplayProps) {
  if (loading) {
    return (
      <div className="bg-gray-50 rounded-xl border border-gray-200 p-4 flex items-center gap-2 text-sm text-gray-500">
        <Loader2 size={14} className="animate-spin" />
        Calculating pricing…
      </div>
    )
  }

  if (!pricing) {
    return (
      <div className="bg-gray-50 rounded-xl border border-dashed border-gray-200 p-4 text-sm text-gray-400 text-center">
        Select a client to see estimated pricing
      </div>
    )
  }

  return (
    <div className="bg-blue-50 rounded-xl border border-blue-200 p-4 space-y-2">
      <div className="flex items-center gap-2 mb-3">
        <Tag size={14} className="text-blue-600" />
        <p className="text-sm font-semibold text-blue-800">Estimated Delivery Cost</p>
        {!pricing.rate_card_id && (
          <span className="text-xs bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded">Default rate</span>
        )}
      </div>

      {pricing.line_items.map((item, i) => (
        <div key={i} className="flex justify-between text-sm">
          <span className="text-gray-700">{item.label}</span>
          <span className="text-gray-900 font-medium">{formatCurrency(item.amount, pricing.currency)}</span>
        </div>
      ))}

      <div className="flex justify-between text-sm border-t border-blue-200 pt-2">
        <span className="text-gray-600">VAT (7.5%)</span>
        <span className="text-gray-700">{formatCurrency(pricing.tax, pricing.currency)}</span>
      </div>

      <div className="flex justify-between text-base font-bold border-t border-blue-300 pt-2">
        <span className="text-blue-800">Total</span>
        <span className="text-blue-900">{formatCurrency(pricing.total, pricing.currency)}</span>
      </div>
    </div>
  )
}
