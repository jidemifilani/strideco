import { cn } from '@/lib/utils'
import type { LucideIcon } from 'lucide-react'

export function Card({ className, children }: { className?: string; children: React.ReactNode }) {
  return (
    <div className={cn('bg-white rounded-xl border border-gray-200 shadow-sm', className)}>
      {children}
    </div>
  )
}

export function CardHeader({ className, children }: { className?: string; children: React.ReactNode }) {
  return (
    <div className={cn('px-6 py-4 border-b border-gray-100 flex items-center justify-between', className)}>
      {children}
    </div>
  )
}

export function CardTitle({ className, children }: { className?: string; children: React.ReactNode }) {
  return <h3 className={cn('text-base font-semibold text-gray-900', className)}>{children}</h3>
}

export function CardBody({ className, children }: { className?: string; children: React.ReactNode }) {
  return <div className={cn('px-6 py-4', className)}>{children}</div>
}

export function StatCard({
  title,
  value,
  sub,
  icon: Icon,
  trend,
  color = 'blue',
}: {
  title: string
  value: string | number
  sub?: string
  icon?: LucideIcon
  trend?: number
  color?: 'blue' | 'green' | 'yellow' | 'red' | 'purple'
}) {
  const colors: Record<string, string> = {
    blue:   'bg-blue-50 text-blue-600',
    green:  'bg-green-50 text-green-600',
    yellow: 'bg-yellow-50 text-yellow-600',
    red:    'bg-red-50 text-red-600',
    purple: 'bg-purple-50 text-purple-600',
  }

  return (
    <Card>
      <CardBody className="flex items-start justify-between gap-4">
        <div>
          <p className="text-sm text-gray-500 mb-1">{title}</p>
          <p className="text-2xl font-bold text-gray-900">{value}</p>
          {sub && <p className="text-xs text-gray-400 mt-1">{sub}</p>}
          {trend !== undefined && (
            <p className={cn('text-xs mt-1 font-medium', trend >= 0 ? 'text-green-600' : 'text-red-600')}>
              {trend >= 0 ? '▲' : '▼'} {Math.abs(trend)}% vs last month
            </p>
          )}
        </div>
        {Icon && (
          <div className={cn('p-3 rounded-lg shrink-0', colors[color])}>
            <Icon size={20} />
          </div>
        )}
      </CardBody>
    </Card>
  )
}
