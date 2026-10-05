'use client'

import { useState } from 'react'
import { Plus, Trash2, Package } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

export interface LineItem {
  id: string
  description: string
  quantity: number
  weight_kg: string
  barcode: string
  sku: string
}

interface OrderItemsFormProps {
  items: LineItem[]
  onChange: (items: LineItem[]) => void
}

function blank(): LineItem {
  return { id: crypto.randomUUID(), description: '', quantity: 1, weight_kg: '', barcode: '', sku: '' }
}

export function OrderItemsForm({ items, onChange }: OrderItemsFormProps) {
  function add() {
    onChange([...items, blank()])
  }

  function remove(id: string) {
    onChange(items.filter(i => i.id !== id))
  }

  function update(id: string, key: keyof LineItem, value: string | number) {
    onChange(items.map(i => i.id === id ? { ...i, [key]: value } : i))
  }

  const totalWeight = items.reduce((sum, i) => sum + (parseFloat(i.weight_kg) || 0), 0)
  const totalQty = items.reduce((sum, i) => sum + (i.quantity || 0), 0)

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <Package size={15} className="text-gray-500" />
          <span className="text-sm font-medium text-gray-700">
            Items manifest
          </span>
          {items.length > 0 && (
            <span className="text-xs text-gray-400">
              {totalQty} units · {totalWeight.toFixed(1)}kg total
            </span>
          )}
        </div>
        <Button type="button" variant="outline" size="sm" onClick={add}>
          <Plus size={13} /> Add item
        </Button>
      </div>

      {items.length > 0 && (
        <div className="border border-gray-200 rounded-xl overflow-hidden">
          <div className="grid grid-cols-[2fr_1fr_1fr_1fr_1fr_auto] gap-px bg-gray-200">
            {/* Header */}
            <div className="bg-gray-50 px-3 py-2 text-xs font-medium text-gray-500 uppercase">Description *</div>
            <div className="bg-gray-50 px-3 py-2 text-xs font-medium text-gray-500 uppercase">Qty</div>
            <div className="bg-gray-50 px-3 py-2 text-xs font-medium text-gray-500 uppercase">Weight (kg)</div>
            <div className="bg-gray-50 px-3 py-2 text-xs font-medium text-gray-500 uppercase">Barcode</div>
            <div className="bg-gray-50 px-3 py-2 text-xs font-medium text-gray-500 uppercase">SKU</div>
            <div className="bg-gray-50 px-3 py-2" />

            {/* Rows */}
            {items.map((item, idx) => (
              <>
                <div key={`${item.id}-desc`} className="bg-white px-1 py-1">
                  <input
                    required
                    value={item.description}
                    onChange={e => update(item.id, 'description', e.target.value)}
                    placeholder="Item description"
                    className="w-full px-2 py-1.5 text-sm bg-transparent outline-none focus:bg-gray-50 rounded"
                  />
                </div>
                <div key={`${item.id}-qty`} className="bg-white px-1 py-1">
                  <input
                    type="number" min={1}
                    value={item.quantity}
                    onChange={e => update(item.id, 'quantity', parseInt(e.target.value) || 1)}
                    className="w-full px-2 py-1.5 text-sm bg-transparent outline-none focus:bg-gray-50 rounded text-center"
                  />
                </div>
                <div key={`${item.id}-wt`} className="bg-white px-1 py-1">
                  <input
                    type="number" step="0.1" min={0}
                    value={item.weight_kg}
                    onChange={e => update(item.id, 'weight_kg', e.target.value)}
                    placeholder="0.0"
                    className="w-full px-2 py-1.5 text-sm bg-transparent outline-none focus:bg-gray-50 rounded text-center"
                  />
                </div>
                <div key={`${item.id}-bc`} className="bg-white px-1 py-1">
                  <input
                    value={item.barcode}
                    onChange={e => update(item.id, 'barcode', e.target.value)}
                    placeholder="Barcode"
                    className="w-full px-2 py-1.5 text-sm bg-transparent outline-none focus:bg-gray-50 rounded font-mono"
                  />
                </div>
                <div key={`${item.id}-sku`} className="bg-white px-1 py-1">
                  <input
                    value={item.sku}
                    onChange={e => update(item.id, 'sku', e.target.value)}
                    placeholder="SKU"
                    className="w-full px-2 py-1.5 text-sm bg-transparent outline-none focus:bg-gray-50 rounded font-mono"
                  />
                </div>
                <div key={`${item.id}-del`} className="bg-white px-2 py-1 flex items-center">
                  <button
                    type="button"
                    onClick={() => remove(item.id)}
                    className="p-1 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded transition-colors"
                  >
                    <Trash2 size={14} />
                  </button>
                </div>
              </>
            ))}
          </div>
        </div>
      )}

      {items.length === 0 && (
        <div
          className="border-2 border-dashed border-gray-200 rounded-xl py-6 text-center cursor-pointer hover:border-blue-300 hover:bg-blue-50 transition-colors"
          onClick={add}
        >
          <Package size={20} className="mx-auto text-gray-300 mb-2" />
          <p className="text-sm text-gray-400">Click to add the first item</p>
        </div>
      )}
    </div>
  )
}
