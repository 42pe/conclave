import { test, expect } from '../fixtures/auth';

test.describe('Admin User Management', () => {
  test('8.1: admin can see user management page', async ({ adminPage: page }) => {
    await page.goto('/admin/users');
    await page.waitForLoadState('networkidle');

    // Verify the users page loaded with heading and table
    await expect(page.locator('h2', { hasText: 'Users' })).toBeVisible({ timeout: 10000 });
    await expect(page.getByText(/Manage forum users/).first()).toBeVisible();
    await expect(page.getByRole('table')).toBeVisible();

    // Verify at least some users appear in the table
    const rows = page.getByRole('row');
    const count = await rows.count();
    expect(count).toBeGreaterThan(1);
  });

  test.describe.serial('Suspend and unsuspend user', () => {
    test('8.2: admin can suspend a user', async ({ adminPage: page }) => {
      await page.goto('/admin/users');

      // Find the row for "verbose-talker99" user
      const verboseRow = page.getByRole('row').filter({ hasText: 'verbose-talker99' });
      await expect(verboseRow).toBeVisible();

      // Open the actions dropdown
      await verboseRow.getByRole('button').click();

      // Click suspend
      await page.getByRole('menuitem', { name: 'Suspend' }).click();

      // Confirm in the alert dialog
      await expect(page.getByRole('heading', { name: 'Suspend User' })).toBeVisible();
      await page.getByRole('button', { name: 'Suspend' }).click();

      // Verify "Suspended" badge appears on that row
      await expect(verboseRow.getByText('Suspended')).toBeVisible();
    });

    test('8.3: admin can unsuspend a user', async ({ adminPage: page }) => {
      await page.goto('/admin/users');

      // Find the row for "verbose-talker99" user which should now be suspended
      const verboseRow = page.getByRole('row').filter({ hasText: 'verbose-talker99' });
      await expect(verboseRow.getByText('Suspended')).toBeVisible();

      // Open the actions dropdown
      await verboseRow.getByRole('button').click();

      // Click unsuspend
      await page.getByRole('menuitem', { name: 'Unsuspend' }).click();

      // Verify "Active" badge appears instead of "Suspended"
      await expect(verboseRow.getByText('Active')).toBeVisible();
      await expect(verboseRow.getByText('Suspended')).not.toBeVisible();
    });
  });
});
