import { test, expect } from '../fixtures/auth';

test.describe('Profile Settings', () => {
  test('can update profile fields', async ({ authenticatedPage: page }) => {
    await page.goto('/settings/profile');

    await page.getByLabel('First name').fill('John');
    await page.getByLabel('Last name').fill('Doe');
    await page.getByLabel('Preferred name').fill('Johnny');
    await page.getByLabel('Bio').fill('A short bio about me.');
    await page.getByRole('button', { name: 'Save' }).click();

    await expect(page.getByText('Saved')).toBeVisible();
  });

  test('profile changes persist after reload', async ({ authenticatedPage: page }) => {
    await page.goto('/settings/profile');

    await page.getByLabel('First name').fill('Jane');
    await page.getByLabel('Last name').fill('Smith');
    await page.getByLabel('Preferred name').fill('Janey');
    await page.getByLabel('Bio').fill('Updated bio content.');
    await page.getByRole('button', { name: 'Save' }).click();

    await expect(page.getByText('Saved')).toBeVisible();

    await page.reload();

    await expect(page.getByLabel('First name')).toHaveValue('Jane');
    await expect(page.getByLabel('Last name')).toHaveValue('Smith');
    await expect(page.getByLabel('Preferred name')).toHaveValue('Janey');
    await expect(page.getByLabel('Bio')).toHaveValue('Updated bio content.');
  });

  test('shows validation error for invalid username', async ({ authenticatedPage: page }) => {
    await page.goto('/settings/profile');

    await page.getByLabel('Username').clear();
    await page.getByLabel('Username').fill('NO');
    await page.getByRole('button', { name: 'Save' }).click();

    await expect(page.locator('text=username')).toBeVisible();
  });
});
