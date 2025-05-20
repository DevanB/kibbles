import { createInertiaApp } from '@inertiajs/react'
import { createElement, ReactNode } from 'react'
import { createRoot } from 'react-dom/client'

import { initializeTheme } from '@/hooks/useAppearance'
import { PersistentLayout } from '@/layouts/PersistentLayout'

// Temporary type definition, until @inertiajs/react provides one
type ResolvedComponent = {
  default: ReactNode & { layout?: (page: ReactNode) => ReactNode }
}

const appName = (import.meta.env.VITE_APP_NAME ?? "Kibbles") as string

createInertiaApp({
  title: title => title ? `${title} :: ${appName}` : appName,

  resolve: (name) => {
    const pages = import.meta.glob<ResolvedComponent>('../pages/**/*.tsx', {
      eager: true,
    })
    const page = pages[`../pages/${name}.tsx`]
    if (!page) {
      console.error(`Missing Inertia page component: '${name}.tsx'`)
    }
    page.default.layout ??= (page) => createElement(PersistentLayout, null, page)

    return page
  },

  setup({ el, App, props }) {
    if (el) {
      createRoot(el).render(createElement(App, props))
    } else {
      console.error(
        'Missing root element.\n\n' +
        'If you see this error, it probably means you load Inertia.js on non-Inertia pages.\n' +
        'Consider moving <%= vite_typescript_tag "inertia" %> to the Inertia-specific layout instead.',
      )
    }
  },
})

// This will set light / dark mode on load...
initializeTheme()
