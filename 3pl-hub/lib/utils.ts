import { clsx, type ClassValue } from 'clsx'
import { twMerge } from 'tailwind-merge'

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export function formatCurrency(amount: number, currency = 'NGN') {
  return new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency,
    minimumFractionDigits: 0,
  }).format(amount)
}

export function formatDate(date: string | Date) {
  return new Intl.DateTimeFormat('en-NG', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(new Date(date))
}

export function formatDateTime(date: string | Date) {
  return new Intl.DateTimeFormat('en-NG', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(date))
}

export function generateOrderNumber() {
  const prefix = 'SBL'
  const timestamp = Date.now().toString(36).toUpperCase()
  return `${prefix}-${timestamp}`
}

export function statusColor(status: string): string {
  const map: Record<string, string> = {
    pending:    'bg-yellow-100 text-yellow-800',
    confirmed:  'bg-blue-100 text-blue-800',
    dispatched: 'bg-indigo-100 text-indigo-800',
    in_transit: 'bg-purple-100 text-purple-800',
    delivered:  'bg-green-100 text-green-800',
    failed:     'bg-red-100 text-red-800',
    returned:   'bg-orange-100 text-orange-800',
    cancelled:  'bg-gray-100 text-gray-700',
    available:  'bg-green-100 text-green-800',
    in_use:     'bg-blue-100 text-blue-800',
    maintenance:'bg-yellow-100 text-yellow-800',
    retired:    'bg-gray-100 text-gray-600',
    approved:   'bg-green-100 text-green-800',
    rejected:   'bg-red-100 text-red-800',
    in_review:  'bg-blue-100 text-blue-800',
    draft:      'bg-gray-100 text-gray-700',
    issued:     'bg-blue-100 text-blue-800',
    paid:       'bg-green-100 text-green-800',
    overdue:    'bg-red-100 text-red-800',
    disputed:   'bg-orange-100 text-orange-800',
  }
  return map[status] ?? 'bg-gray-100 text-gray-700'
}
