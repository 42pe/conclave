import { test as base, type Page } from '@playwright/test';

type Credentials = {
  email: string;
  password: string;
};

type AuthFixtures = {
  authenticatedPage: Page;
  login: (page: Page, credentials: Credentials) => Promise<void>;
};

export const test = base.extend<AuthFixtures>({
  login: async ({}, use) => {
    const loginFn = async (page: Page, credentials: Credentials) => {
      await page.goto('/login');
      await page.getByLabel('Email').fill(credentials.email);
      await page.getByLabel('Password').fill(credentials.password);
      await page.getByRole('button', { name: 'Log in' }).click();
      await page.waitForURL('**/dashboard');
    };
    await use(loginFn);
  },

  authenticatedPage: async ({ page, login }, use) => {
    await login(page, {
      email: 'test@example.com',
      password: 'password',
    });
    await use(page);
  },
});

export { expect } from '@playwright/test';
