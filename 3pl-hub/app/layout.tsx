import type { Metadata } from 'next'
import './globals.css'

export const metadata: Metadata = {
  title: '3PL Hub — Sunbeth Logistics',
  description: 'Last-Mile Distribution Network Management Platform',
}

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  )
}
