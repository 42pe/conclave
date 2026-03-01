import { test, expect } from '../fixtures/auth';

test.describe('Views', () => {
    test('16.2: View count on discussion detail', async ({ authenticatedPage: page }) => {
        await page.goto('/topics/general-discussion');

        // Navigate to a discussion
        await page.getByRole('heading', { name: 'Welcome to Conclave' }).click();
        await page.waitForURL(/\/topics\/general-discussion\/discussions\//);

        // Verify view count is visible on the discussion detail page
        // The detail page shows "X views" text in the footer area
        await expect(page.getByText(/\d+\s+views/)).toBeVisible();
    });
});
