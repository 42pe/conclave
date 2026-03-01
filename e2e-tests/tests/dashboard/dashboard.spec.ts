import { test, expect } from '../fixtures/auth';

test.describe('Dashboard', () => {
  test('11.4: dashboard loads with stats', async ({ authenticatedPage: page }) => {
    await page.goto('/dashboard');

    // Verify the "Your Stats" card is visible
    await expect(page.getByText('Your Stats').first()).toBeVisible();

    // Verify stat labels are present
    await expect(page.getByText('Discussions').first()).toBeVisible();
    await expect(page.getByText('Replies').first()).toBeVisible();

    // Verify Quick Actions card
    await expect(page.getByText('Quick Actions')).toBeVisible();
  });

  test('11.5: dashboard shows recent discussions', async ({ authenticatedPage: page }) => {
    await page.goto('/dashboard');

    // Verify the Recent Discussions section header
    await expect(page.getByText('Recent Discussions')).toBeVisible();

    // Wait for deferred props to load (skeleton replaced by content)
    const recentDiscussionsCard = page.locator('text=Recent Discussions').locator('..').locator('..').locator('..');

    // Wait for skeleton to disappear (deferred props loaded)
    await expect(recentDiscussionsCard.locator('[class*="animate-pulse"]')).toHaveCount(0, { timeout: 10000 });

    // Check for discussion links or empty state
    const hasDiscussions = await recentDiscussionsCard.getByRole('link').count() > 0;
    const hasEmptyState = await recentDiscussionsCard.getByText('No recent discussions.').isVisible().catch(() => false);

    expect(hasDiscussions || hasEmptyState).toBe(true);
  });
});
