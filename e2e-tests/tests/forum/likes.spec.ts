import { test as guestTest, expect as guestExpect } from '@playwright/test';
import { test, expect } from '../fixtures/auth';

test.describe('Likes - Authenticated', () => {
    test('14.1: Like button visible on discussion', async ({ authenticatedPage: page }) => {
        await page.goto('/topics/general-discussion');

        // Navigate to a discussion detail page
        await page.getByText('Welcome to Conclave').first().click();
        await page.waitForURL(/\/topics\/general-discussion\/discussions\//);

        // Verify the heart/like button is visible in the discussion footer
        // The like button is a ghost Button with a Heart SVG and a count
        const footer = page.locator('.border-t').first();
        const likeButton = footer.getByRole('button').filter({ has: page.locator('svg.size-3\\.5') }).first();
        await expect(likeButton).toBeVisible();
    });

    test.describe.serial('14.2-14.4: Like, unlike, and persist', () => {
        test('14.2: Can like a discussion', async ({ secondUserPage: page }) => {
            await page.goto('/topics/general-discussion');
            await page.getByText('Welcome to Conclave').first().click();
            await page.waitForURL(/\/topics\/general-discussion\/discussions\//);

            // Find the like button in the discussion body footer
            const footer = page.locator('.border-t').first();
            const likeButton = footer.getByRole('button').first();

            // Get the initial count text
            const initialCountText = await likeButton.textContent();
            const initialCount = parseInt(initialCountText?.trim() || '0', 10);

            // Click the like button and wait for the response
            const responsePromise = page.waitForResponse(
                (resp) => resp.url().includes('/like') && resp.status() === 200,
            );
            await likeButton.click();
            await responsePromise;

            // Verify the heart icon has the fill class (text-red-500)
            await expect(likeButton.locator('svg.text-red-500')).toBeVisible();

            // Verify the count incremented
            const newCountText = await likeButton.textContent();
            const newCount = parseInt(newCountText?.trim() || '0', 10);
            expect(newCount).toBe(initialCount + 1);
        });

        test('14.3: Can unlike a discussion', async ({ secondUserPage: page }) => {
            await page.goto('/topics/general-discussion');
            await page.getByText('Welcome to Conclave').first().click();
            await page.waitForURL(/\/topics\/general-discussion\/discussions\//);

            const footer = page.locator('.border-t').first();
            const likeButton = footer.getByRole('button').first();

            // Should currently be liked (from previous test)
            await expect(likeButton.locator('svg.text-red-500')).toBeVisible();

            const initialCountText = await likeButton.textContent();
            const initialCount = parseInt(initialCountText?.trim() || '0', 10);

            // Click to unlike
            const responsePromise = page.waitForResponse(
                (resp) => resp.url().includes('/like') && resp.status() === 200,
            );
            await likeButton.click();
            await responsePromise;

            // Verify the heart icon no longer has the fill class
            await expect(likeButton.locator('svg.text-red-500')).not.toBeVisible();

            // Verify the count decremented
            const newCountText = await likeButton.textContent();
            const newCount = parseInt(newCountText?.trim() || '0', 10);
            expect(newCount).toBe(initialCount - 1);
        });

        test('14.4: Like persists after reload', async ({ secondUserPage: page }) => {
            await page.goto('/topics/general-discussion');
            await page.getByText('Welcome to Conclave').first().click();
            await page.waitForURL(/\/topics\/general-discussion\/discussions\//);

            const footer = page.locator('.border-t').first();
            const likeButton = footer.getByRole('button').first();

            // Like the discussion
            const responsePromise = page.waitForResponse(
                (resp) => resp.url().includes('/like') && resp.status() === 200,
            );
            await likeButton.click();
            await responsePromise;

            // Verify it is liked
            await expect(likeButton.locator('svg.text-red-500')).toBeVisible();

            // Reload the page
            await page.reload();

            // Verify the like persisted after reload
            const footerAfter = page.locator('.border-t').first();
            const likeButtonAfter = footerAfter.getByRole('button').first();
            await expect(likeButtonAfter.locator('svg.text-red-500')).toBeVisible();
        });
    });
});

guestTest.describe('Likes - Guest', () => {
    guestTest('14.7: Guest sees like counts but no clickable button', async ({ page }) => {
        await page.goto('/topics/general-discussion');
        await page.getByText('Welcome to Conclave').first().click();
        await page.waitForURL(/\/topics\/general-discussion\/discussions\//);

        const footer = page.locator('.border-t').first();

        // Guest should see view count
        await guestExpect(footer.getByText(/\d+\s+views/)).toBeVisible();

        // Guest should NOT see a like button (it renders as a span, not a button)
        await guestExpect(footer.getByRole('button').filter({ hasText: /^\d+$/ })).not.toBeVisible();
    });
});
