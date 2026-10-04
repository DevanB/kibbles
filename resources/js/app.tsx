import { createInertiaApp } from '@inertiajs/react';
import {
    ModalStackProvider,
    initFromPageProps,
    putConfig,
} from '@inertiaui/modal-react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { ComponentType } from 'react';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import '../css/app.css';

putConfig('navigate', true);
putConfig(
    'modal.panelClasses',
    'bg-background text-foreground rounded-xl border border-border shadow-lg',
);

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent<ComponentType>(
            `./pages/${name}.tsx`,
            import.meta.glob<ComponentType>('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        initFromPageProps(props as never);
        const root = createRoot(el);

        root.render(
            <StrictMode>
                <ModalStackProvider>
                    <TooltipProvider delayDuration={0}>
                        <App {...props} />
                        <Toaster />
                    </TooltipProvider>
                </ModalStackProvider>
            </StrictMode>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
