import { test as guestTest, expect as guestExpect } from '@playwright/test';
import { test, expect } from '../fixtures/auth';

test.describe('Replies - Authenticated', () => {
    test('6.1: Can post reply to discussion', async ({ authenticatedPage: page }) => {
        await page.goto('/topics/general-discussion');

        // Click on "Welcome to Conclave" discussion
        await page.getByText('Welcome to Conclave').first().click();
        await page.waitForURL(/\/topics\/general-discussion\/discussions\//);

        // Verify reply form is present
        await expect(page.getByText('Leave a reply')).toBeVisible();

        // Type in the reply Slate editor (the last contenteditable on the page is the reply form)
        const replyEditor = page.locator('[contenteditable=true]').last();
        await replyEditor.click();
        await replyEditor.fill('This is an automated E2E test reply.');

        // Submit the reply
        await page.getByRole('button', { name: 'Post Reply' }).click();

        // Verify the reply appears on the page
        await expect(page.getByText('This is an automated E2E test reply.')).toBeVisible();
    });
});

guestTest.describe('Replies - Guest', () => {
    guestTest('6.6: Guest cannot reply', async ({ page }) => {
        await page.goto('/topics/general-discussion');

        // Navigate to a discussion
        await page.getByText('Welcome to Conclave').first().click();
        await page.waitForURL(/\/topics\/general-discussion\/discussions\//);

        // Verify the discussion page loaded
        await guestExpect(page.locator('h1', { hasText: 'Welcome to Conclave' })).toBeVisible();

        // Verify no "Leave a reply" section is shown for guests
        await guestExpect(page.getByText('Leave a reply')).not.toBeVisible();

        // Verify no reply form editor is shown
        await guestExpect(page.getByRole('button', { name: 'Post Reply' })).not.toBeVisible();
    });
});
