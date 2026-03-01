import { test as base, type Page, type BrowserContext, type Browser } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

const AUTH_DIR = path.join(__dirname, '..', '..', '.auth');

type Credentials = {
  email: string;
  password: string;
};

type AuthFixtures = {
  authenticatedPage: Page;
  adminPage: Page;
  moderatorPage: Page;
  secondUserPage: Page;
  login: (page: Page, credentials: Credentials) => Promise<void>;
};

function stateFile(name: string): string {
  return path.join(AUTH_DIR, `${name}.json`);
}

async function getOrCreateAuthContext(
  browser: Browser,
  name: string,
  loginFn: (page: Page, credentials: Credentials) => Promise<void>,
  credentials: Credentials,
): Promise<BrowserContext> {
  const file = stateFile(name);

  if (fs.existsSync(file)) {
    return browser.newContext({ storageState: file, ignoreHTTPSErrors: true });
  }

  // First time: login and save state
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  const page = await context.newPage();
  await loginFn(page, credentials);
  await page.close();

  if (!fs.existsSync(AUTH_DIR)) {
    fs.mkdirSync(AUTH_DIR, { recursive: true });
  }
  await context.storageState({ path: file });

  return context;
}

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

  authenticatedPage: async ({ browser, login }, use) => {
    const context = await getOrCreateAuthContext(browser, 'user', login, {
      email: 'test@example.com',
      password: 'password',
    });
    const page = await context.newPage();
    await use(page);
    await context.close();
  },

  adminPage: async ({ browser, login }, use) => {
    const context = await getOrCreateAuthContext(browser, 'admin', login, {
      email: 'admin@example.com',
      password: 'password',
    });
    const page = await context.newPage();
    await use(page);
    await context.close();
  },

  moderatorPage: async ({ browser, login }, use) => {
    const context = await getOrCreateAuthContext(browser, 'moderator', login, {
      email: 'moderator@example.com',
      password: 'password',
    });
    const page = await context.newPage();
    await use(page);
    await context.close();
  },

  secondUserPage: async ({ browser, login }, use) => {
    const context = await getOrCreateAuthContext(browser, 'seconduser', login, {
      email: 'minimal@example.com',
      password: 'password',
    });
    const page = await context.newPage();
    await use(page);
    await context.close();
  },
});

export { expect } from '@playwright/test';
