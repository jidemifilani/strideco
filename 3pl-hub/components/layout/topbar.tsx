'use client'

import { Bell, Search } from 'lucide-react'
import type { Profile } from '@/types'

interface TopbarProps {
  profile?: Profile | null
  title?: string
}

export function Topbar({ profile, title }: TopbarProps) {
  return (
    <header className="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 shrink-0">
      <div className="flex items-center gap-4">
        {title && <h1 className="text-lg font-semibold text-gray-900">{title}</h1>}
        <div className="relative hidden md:block">
          <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input
            type="search"
            placeholder="Search orders, routes, clients…"
            className="pl-9 pr-4 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg w-64 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>
      </div>

      <div className="flex items-center gap-3">
        <button className="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
          <Bell size={20} />
          <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full" />
        </button>

        <div className="flex items-center gap-2 pl-3 border-l border-gray-200">
          <div className="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-xs font-semibold">
            {profile?.full_name?.[0]?.toUpperCase() ?? 'U'}
          </div>
          <div className="hidden md:block">
            <p className="text-sm font-medium text-gray-900 leading-tight">{profile?.full_name ?? 'User'}</p>
            <p className="text-xs text-gray-500 capitalize">{profile?.role?.replace(/_/g, ' ')}</p>
          </div>
        </div>
      </div>
    </header>
  )
}
