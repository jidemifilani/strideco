'use client'

import Link from 'next/link'
import { usePathname } from 'next/navigation'
import {
  LayoutDashboard,
  ShoppingCart,
  Truck,
  Map,
  PackageCheck,
  Users,
  Receipt,
  Settings,
  LogOut,
} from 'lucide-react'
import { cn } from '@/lib/utils'
import { createClient } from '@/lib/supabase/client'
import { useRouter } from 'next/navigation'

const NAV = [
  {
    label: 'Overview',
    href: '/dashboard',
    icon: LayoutDashboard,
    exact: true,
  },
  {
    label: 'Orders',
    href: '/dashboard/orders',
    icon: ShoppingCart,
    badge: 'M01',
  },
  {
    label: 'Fleet',
    href: '/dashboard/fleet',
    icon: Truck,
    badge: 'M02',
  },
  {
    label: 'Routes',
    href: '/dashboard/routes',
    icon: Map,
    badge: 'M03',
  },
  {
    label: 'Deliveries',
    href: '/dashboard/deliveries',
    icon: PackageCheck,
    badge: 'M04',
  },
  {
    label: 'Clients',
    href: '/dashboard/clients',
    icon: Users,
    badge: 'M05',
  },
  {
    label: 'Billing',
    href: '/dashboard/billing',
    icon: Receipt,
    badge: 'M06',
  },
]

export function Sidebar() {
  const pathname = usePathname()
  const router = useRouter()
  const supabase = createClient()

  async function handleSignOut() {
    await supabase.auth.signOut()
    router.push('/auth/login')
    router.refresh()
  }

  return (
    <aside className="w-64 shrink-0 h-screen sticky top-0 bg-gray-900 flex flex-col">
      {/* Logo */}
      <div className="px-6 py-5 border-b border-gray-800">
        <div className="flex items-center gap-2">
          <div className="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center text-white font-bold text-sm">
            3PL
          </div>
          <div>
            <p className="text-white font-semibold text-sm leading-tight">3PL Hub</p>
            <p className="text-gray-400 text-xs">Sunbeth Logistics</p>
          </div>
        </div>
      </div>

      {/* Navigation */}
      <nav className="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        {NAV.map(({ label, href, icon: Icon, exact, badge }) => {
          const active = exact ? pathname === href : pathname.startsWith(href)
          return (
            <Link
              key={href}
              href={href}
              className={cn(
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                active
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-400 hover:bg-gray-800 hover:text-white'
              )}
            >
              <Icon size={18} />
              <span className="flex-1">{label}</span>
              {badge && !active && (
                <span className="text-[10px] bg-gray-800 text-gray-500 px-1.5 py-0.5 rounded font-mono">
                  {badge}
                </span>
              )}
            </Link>
          )
        })}
      </nav>

      {/* Footer */}
      <div className="px-3 py-3 border-t border-gray-800 space-y-0.5">
        <Link
          href="/dashboard/settings"
          className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-400 hover:bg-gray-800 hover:text-white transition-colors"
        >
          <Settings size={18} />
          Settings
        </Link>
        <button
          onClick={handleSignOut}
          className="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-400 hover:bg-red-900/40 hover:text-red-400 transition-colors"
        >
          <LogOut size={18} />
          Sign Out
        </button>
      </div>
    </aside>
  )
}
