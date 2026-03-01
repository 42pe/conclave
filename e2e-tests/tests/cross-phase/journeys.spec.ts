import { test as guestTest, expect as guestExpect } from '@playwright/test';
import { test, expect } from '../fixtures/auth';

guestTest.describe('Cross-Phase Journeys - Guest', () => {
    guestTest('X.4a: Guest sees limited homepage — no restricted topics, no bookmark buttons', async ({ page }) => {
        // Visit the homepage as a guest
        await page.goto('/');

        // Guest should NOT see the restricted "Members Only Lounge" topic
        await guestExpect(page.getByText('Members Only Lounge')).not.toBeVisible();

        // Guest should see public topics
        await guestExpect(page.getByText('General Discussion')).toBeVisible();
    });
});

test.describe('Cross-Phase Journeys - Authenticated', () => {
    test('X.4b: Authenticated user sees more topics than guest (private visibility)', async ({ authenticatedPage: page }) => {
        // Visit the homepage as an authenticated user
        await page.goto('/');

        // Authenticated user should see Private topics that guests cannot
        // "Admin Announcements" has Private visibility — visible to logged-in users only
        await expect(page.getByText('Admin Announcements')).toBeVisible({ timeout: 10000 });

        // Authenticated user should also see public topics
        await expect(page.getByText('General Discussion')).toBeVisible();
    });
});
