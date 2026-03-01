import { test as guestTest, expect as guestExpect } from '@playwright/test';
import { test, expect } from '../fixtures/auth';

guestTest.describe('Topics - Guest', () => {
    guestTest('3.1: Homepage shows public topics to guest', async ({ page }) => {
        await page.goto('/');

        await guestExpect(page.getByText('General Discussion')).toBeVisible();
        await guestExpect(page.getByText('Technology & Code')).toBeVisible();
        await guestExpect(page.getByText('Career & Professional')).toBeVisible();
        await guestExpect(page.getByText('Community Events')).toBeVisible();
    });

    guestTest('3.2: Homepage hides restricted topics from guest', async ({ page }) => {
        await page.goto('/');

        await guestExpect(page.getByText('Members Only Lounge')).not.toBeVisible();
    });

    guestTest('3.8: Topic card shows discussion count', async ({ page }) => {
        await page.goto('/');

        // Topic cards display discussion counts (e.g. "2 discussions" or "0 discussions")
        await guestExpect(page.locator('section').filter({ hasText: 'Forum Topics' })).toBeVisible();

        // Verify at least one card shows a discussion count pattern
        await guestExpect(page.getByText(/\d+\s+discussions?/).first()).toBeVisible();
    });

    guestTest('3.10: Private topic requires login', async ({ page }) => {
        await page.goto('/topics/admin-announcements');

        await guestExpect(page).toHaveURL(/\/login/);
    });
});

test.describe('Topics - Authenticated', () => {
    test('3.3: Private topics visible to logged-in user', async ({ authenticatedPage: page }) => {
        await page.goto('/');

        // Regular logged-in users see Public + Private topics (not Restricted which is admin-only)
        // "Admin Announcements" is a Private visibility topic — visible to logged-in users but not guests
        await expect(page.getByText('Admin Announcements')).toBeVisible({ timeout: 10000 });
    });
});
