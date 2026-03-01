import { test, expect } from '@playwright/test';

test.describe('User Profile', () => {
  test('7.1: user profile page loads with username', async ({ page }) => {
    // Use the admin profile to avoid data dependency on testuser (profile settings tests modify testuser)
    await page.goto('/users/admin');

    // The heading shows the user's display_name
    await expect(page.getByRole('heading', { name: 'Admin User' })).toBeVisible();
    await expect(page.getByText('@admin')).toBeVisible();
  });

  test('7.4: deleted user shows "Deleted User"', async ({ page }) => {
    await page.goto('/users/deleted-user');

    await expect(page.getByRole('heading', { name: 'Deleted User' })).toBeVisible();
  });
});
