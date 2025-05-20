import type { ReactNode } from "react"

import { Toaster } from "@/components/ui/sonner"
import { useFlash } from "@/hooks/useFlash"

interface PersistentLayoutProps {
  children: ReactNode
}

export function PersistentLayout({ children }: PersistentLayoutProps) {
  useFlash()
  return (
    <>
      {children}
      <Toaster expand={false} richColors />
    </>
  )
}
