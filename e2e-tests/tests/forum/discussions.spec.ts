import { test as guestTest, expect as guestExpect } from '@playwright/test';
import { test, expect } from '../fixtures/auth';

guestTest.describe('Discussions - Guest', () => {
    guestTest('5.9: Guest can view discussions', async ({ page }) => {
        await page.goto('/topics/general-discussion');

        // Verify discussion cards are visible on the topic page
        await guestExpect(page.getByText('Welcome to Conclave')).toBeVisible();

        // Click on a discussion card to navigate to the detail page
        await page.getByText('Welcome to Conclave').click();
        await page.waitForURL(/\/topics\/general-discussion\/discussions\//);

        // Verify the discussion detail renders
        await guestExpect(page.locator('h1', { hasText: 'Welcome to Conclave' })).toBeVisible();
    });

    guestTest('5.10: Guest cannot create discussion', async ({ page }) => {
        await page.goto('/topics/general-discussion');

        // "New Discussion" button should not be visible for guests
        await guestExpect(page.getByRole('link', { name: 'New Discussion' })).not.toBeVisible();
    });
});

test.describe('Discussions - Authenticated', () => {
    test('5.1: Topic page lists discussions', async ({ authenticatedPage: page }) => {
        await page.goto('/topics/general-discussion');

        // Verify discussion cards are present (seeded discussions)
        await expect(page.getByRole('heading', { name: 'Welcome to Conclave' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'What are you working on this week?' })).toBeVisible();
    });

    test('5.2: Pinned discussions appear first', async ({ authenticatedPage: page }) => {
        await page.goto('/topics/general-discussion');

        // Wait for discussions to load
        await expect(page.getByRole('heading', { name: 'Welcome to Conclave' })).toBeVisible();

        // The first h3 heading in the discussion list should be the pinned "Welcome to Conclave"
        await expect(page.locator('main h3').first()).toContainText('Welcome to Conclave');
    });

    test('5.3: Can create discussion', async ({ authenticatedPage: page }) => {
        await page.goto('/topics/general-discussion');

        // Click "New Discussion" button
        await page.getByRole('link', { name: 'New Discussion' }).click();
        await page.waitForURL(/\/topics\/general-discussion\/discussions\/create/);

        // Fill in the title
        await page.getByLabel('Title').fill('E2E Test Discussion');

        // Type in the Slate editor
        const editor = page.locator('[contenteditable=true]').first();
        await editor.click();
        await editor.fill('This is an automated E2E test discussion body.');

        // Submit the form
        await page.getByRole('button', { name: 'Create Discussion' }).click();

        // Verify redirected to the new discussion detail page
        await page.waitForURL(/\/topics\/general-discussion\/discussions\/e2e-test-discussion/);
        await expect(page.locator('h1', { hasText: 'E2E Test Discussion' })).toBeVisible();
    });

    test('5.4: Discussion detail page renders', async ({ authenticatedPage: page }) => {
        await page.goto('/topics/general-discussion');

        // Click on the "Welcome to Conclave" discussion
        await page.getByRole('heading', { name: 'Welcome to Conclave' }).click();
        await page.waitForURL(/\/topics\/general-discussion\/discussions\//);

        // Verify title
        await expect(page.locator('h1', { hasText: 'Welcome to Conclave' })).toBeVisible();

        // Verify the replies section is present
        await expect(page.getByText(/Replies \(\d+\)/)).toBeVisible();
    });

    test('5.11: Discussion card shows metadata', async ({ authenticatedPage: page }) => {
        await page.goto('/topics/general-discussion');

        // Wait for discussions to load
        await expect(page.getByRole('heading', { name: 'Welcome to Conclave' })).toBeVisible();

        // Verify author name and time are shown on discussion cards
        await expect(page.locator('main').getByText('Admin User').first()).toBeVisible();
        await expect(page.locator('main').getByText(/ago|just now/).first()).toBeVisible();
    });
});
