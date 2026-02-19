import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import '../css/app.css';
import { initializeTheme } from './hooks/use-appearance';
import { capturePageview, identifyUser, initPostHog } from './lib/posthog';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <StrictMode>
                <App {...props} />
            </StrictMode>,
        );

        // Initialize PostHog
        initPostHog();

        // Identify user if authenticated
        const auth = (props.initialPage.props as Record<string, unknown>)
            .auth as { user?: { id: number; email: string } } | undefined;
        if (auth?.user) {
            identifyUser(auth.user.id, auth.user.email);
        }

        // Track Inertia page navigations
        router.on('navigate', (event) => {
            capturePageview(event.detail.page.url);
        });
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
