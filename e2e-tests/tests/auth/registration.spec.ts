import { test, expect } from '@playwright/test';

test.describe('Registration', () => {
  test('can register with valid fields', async ({ page }) => {
    await page.goto('/register');

    await page.getByLabel('Username').fill('validuser');
    await page.getByLabel('Name', { exact: true }).fill('Test User');
    await page.getByLabel('Email address').fill('newuser@example.com');
    await page.getByLabel('Password', { exact: true }).fill('password');
    await page.getByLabel('Confirm password').fill('password');
    await page.getByRole('button', { name: 'Create account' }).click();

    await page.waitForURL('**/dashboard');
    await expect(page).toHaveURL(/\/dashboard/);
  });

  test('shows validation error for username too short', async ({ page }) => {
    await page.goto('/register');

    await page.getByLabel('Username').fill('abc');
    await page.getByLabel('Name', { exact: true }).fill('Test User');
    await page.getByLabel('Email address').fill('short@example.com');
    await page.getByLabel('Password', { exact: true }).fill('password');
    await page.getByLabel('Confirm password').fill('password');
    await page.getByRole('button', { name: 'Create account' }).click();

    await expect(page.locator('text=username')).toBeVisible();
  });

  test('shows validation error for username with invalid characters', async ({ page }) => {
    await page.goto('/register');

    await page.getByLabel('Username').fill('Bad.User!');
    await page.getByLabel('Name', { exact: true }).fill('Test User');
    await page.getByLabel('Email address').fill('invalid@example.com');
    await page.getByLabel('Password', { exact: true }).fill('password');
    await page.getByLabel('Confirm password').fill('password');
    await page.getByRole('button', { name: 'Create account' }).click();

    await expect(page.locator('text=username')).toBeVisible();
  });
});
