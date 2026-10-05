'use client'

import { useState } from 'react'
import { Button } from '@/components/ui/button'
import { FailedDeliveryForm } from './failed-delivery-form'
import { AlertTriangle } from 'lucide-react'

interface Props {
  orderId: string
  deliveryId: string
}

export function OrderDetailFailedButton({ orderId, deliveryId }: Props) {
  const [open, setOpen] = useState(false)

  return (
    <>
      <Button
        variant="outline"
        size="sm"
        className="w-full justify-start gap-2 text-red-600 border-red-200 hover:bg-red-50"
        onClick={() => setOpen(true)}
      >
        <AlertTriangle size={14} />
        Record Failed Delivery
      </Button>

      {open && (
        <FailedDeliveryForm
          orderId={orderId}
          deliveryId={deliveryId}
          onClose={() => setOpen(false)}
        />
      )}
    </>
  )
}
