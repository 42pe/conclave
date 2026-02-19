import posthog from 'posthog-js';

const apiKey = import.meta.env.VITE_POSTHOG_KEY as string | undefined;
const apiHost = (import.meta.env.VITE_POSTHOG_HOST as string) || 'https://us.i.posthog.com';

export function initPostHog(): void {
    if (!apiKey) {
        return;
    }

    posthog.init(apiKey, {
        api_host: apiHost,
        capture_pageview: false, // We track Inertia navigations manually
    });
}

export { posthog };
