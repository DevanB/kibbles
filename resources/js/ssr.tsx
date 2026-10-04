import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import {
    ModalRoot,
    ModalStackProvider,
    putConfig,
} from '@inertiaui/modal-react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { ComponentType } from 'react';
import ReactDOMServer from 'react-dom/server';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';

putConfig('navigate', true);
putConfig(
    'modal.panelClasses',
    'bg-background text-foreground rounded-xl border border-border shadow-lg',
);

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => (title ? `${title} - ${appName}` : appName),
        resolve: (name) =>
            resolvePageComponent(
                `./pages/${name}.tsx`,
                import.meta.glob<ComponentType>('./pages/**/*.tsx'),
            ),
        setup: ({ App, props }) => {
            return (
                <ModalStackProvider>
                    <TooltipProvider delayDuration={0}>
                        <App {...props} />
                        <ModalRoot />
                        <Toaster />
                    </TooltipProvider>
                </ModalStackProvider>
            );
        },
    }),
);
