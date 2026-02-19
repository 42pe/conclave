import posthog from 'posthog-js';

const apiKey = import.meta.env.VITE_POSTHOG_KEY as string | undefined;
const host = (import.meta.env.VITE_POSTHOG_HOST as string) || 'https://us.i.posthog.com';

let initialized = false;

export function initPostHog(): void {
    if (!apiKey || initialized) {
        return;
    }

    posthog.init(apiKey, {
        api_host: host,
        capture_pageview: false,
    });

    initialized = true;
}

export function identifyUser(id: number, email: string): void {
    if (!initialized) {
        return;
    }

    posthog.identify(String(id), { email });
}

export function capturePageview(url: string): void {
    if (!initialized) {
        return;
    }

    posthog.capture('$pageview', { $current_url: url });
}

export function resetPostHog(): void {
    if (!initialized) {
        return;
    }

    posthog.reset();
}
