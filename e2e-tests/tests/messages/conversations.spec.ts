import { test as guestTest, expect as guestExpect } from '@playwright/test';
import { test, expect } from '../fixtures/auth';

test.describe('Messages', () => {
  test('9.1: messages page loads', async ({ authenticatedPage: page }) => {
    await page.goto('/messages');

    await expect(page.getByRole('heading', { name: 'Messages' })).toBeVisible();

    // Should show either conversation list or empty state
    const hasConversations = await page.locator('[class*="space-y-2"] a').count() > 0;
    const hasEmptyState = await page.getByText('No conversations yet').isVisible().catch(() => false);

    expect(hasConversations || hasEmptyState).toBe(true);
  });
});

guestTest.describe('Messages - Guest', () => {
  guestTest('9.6: guest cannot access messages', async ({ page }) => {
    await page.goto('/messages');

    // Should be redirected to login page
    await expect(page).toHaveURL(/\/login/);
  });
});
