import { test, expect } from '../fixtures/auth';

test.describe('Member Directory', () => {
  test('7.5: directory page lists users', async ({ authenticatedPage: page }) => {
    await page.goto('/directory');

    await expect(page.getByRole('heading', { name: 'Member Directory' })).toBeVisible();
    await expect(page.getByText('Browse and find community members.')).toBeVisible();

    // Verify user cards are rendered in the grid
    const userCards = page.locator('[class*="grid"] a[href^="/users/"]');
    await expect(userCards.first()).toBeVisible();
    const count = await userCards.count();
    expect(count).toBeGreaterThan(0);
  });

  test('7.6: directory search filters users', async ({ authenticatedPage: page }) => {
    await page.goto('/directory');

    const searchInput = page.getByPlaceholder('Search by name or username...');
    await expect(searchInput).toBeVisible();

    await searchInput.fill('admin');
    await searchInput.press('Enter');

    // After search, the results should be filtered
    await expect(page.getByText('@admin')).toBeVisible();
  });

  test('7.7: directory excludes deleted users', async ({ authenticatedPage: page }) => {
    await page.goto('/directory');

    // Deleted users should not appear in the directory
    await expect(page.getByText('deleted-user')).not.toBeVisible();
    await expect(page.getByText('Deleted User')).not.toBeVisible();
  });
});
