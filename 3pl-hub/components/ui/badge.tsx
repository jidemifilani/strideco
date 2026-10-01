import { cn, statusColor } from '@/lib/utils'

interface BadgeProps {
  status: string
  label?: string
  className?: string
}

export function StatusBadge({ status, label, className }: BadgeProps) {
  return (
    <span
      className={cn(
        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize',
        statusColor(status),
        className
      )}
    >
      {label ?? status.replace(/_/g, ' ')}
    </span>
  )
}

interface ChipProps {
  label: string
  color?: 'blue' | 'green' | 'red' | 'yellow' | 'gray' | 'purple'
  className?: string
}

const colorMap: Record<string, string> = {
  blue:   'bg-blue-100 text-blue-800',
  green:  'bg-green-100 text-green-800',
  red:    'bg-red-100 text-red-800',
  yellow: 'bg-yellow-100 text-yellow-800',
  gray:   'bg-gray-100 text-gray-700',
  purple: 'bg-purple-100 text-purple-800',
}

export function Chip({ label, color = 'gray', className }: ChipProps) {
  return (
    <span className={cn('inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', colorMap[color], className)}>
      {label}
    </span>
  )
}
