import { test, expect } from '../fixtures/auth';

test.describe('Bookmarks', () => {
    test('15.1: Bookmark button visible on discussion', async ({ authenticatedPage: page }) => {
        await page.goto('/topics/general-discussion');
        await page.getByRole('heading', { name: 'Welcome to Conclave' }).click();
        await page.waitForURL(/\/topics\/general-discussion\/discussions\//);

        // Verify the bookmark button is visible in the discussion footer
        await expect(page.getByRole('button', { name: /Bookmark/ })).toBeVisible();
    });

    test.describe.serial('15.2-15.4: Bookmark, unbookmark, and persist', () => {
        test('15.2: Can bookmark a discussion', async ({ secondUserPage: page }) => {
            await page.goto('/topics/general-discussion');
            await page.getByRole('heading', { name: 'Welcome to Conclave' }).click();
            await page.waitForURL(/\/topics\/general-discussion\/discussions\//);

            const bookmarkButton = page.getByRole('button', { name: 'Bookmark', exact: true });

            // Click the bookmark button and wait for the response
            const responsePromise = page.waitForResponse(
                (resp) => resp.url().includes('/bookmark') && resp.status() === 200,
            );
            await bookmarkButton.click();
            await responsePromise;

            // Verify text changes to "Bookmarked"
            await expect(page.getByRole('button', { name: 'Bookmarked' })).toBeVisible();
        });

        test('15.3: Can remove bookmark', async ({ secondUserPage: page }) => {
            await page.goto('/topics/general-discussion');
            await page.getByRole('heading', { name: 'Welcome to Conclave' }).click();
            await page.waitForURL(/\/topics\/general-discussion\/discussions\//);

            const bookmarkButton = page.getByRole('button', { name: 'Bookmarked' });

            // Should be bookmarked from previous test
            await expect(bookmarkButton).toBeVisible();

            // Click to remove bookmark
            const responsePromise = page.waitForResponse(
                (resp) => resp.url().includes('/bookmark') && resp.status() === 200,
            );
            await bookmarkButton.click();
            await responsePromise;

            // Verify text reverts to "Bookmark"
            await expect(page.getByRole('button', { name: 'Bookmark', exact: true })).toBeVisible();
        });

        test('15.4: Bookmark persists after reload', async ({ secondUserPage: page }) => {
            await page.goto('/topics/general-discussion');
            await page.getByRole('heading', { name: 'Welcome to Conclave' }).click();
            await page.waitForURL(/\/topics\/general-discussion\/discussions\//);

            const bookmarkButton = page.getByRole('button', { name: 'Bookmark', exact: true });

            // Bookmark the discussion
            const responsePromise = page.waitForResponse(
                (resp) => resp.url().includes('/bookmark') && resp.status() === 200,
            );
            await bookmarkButton.click();
            await responsePromise;

            // Verify it is bookmarked
            await expect(page.getByRole('button', { name: 'Bookmarked' })).toBeVisible();

            // Reload the page
            await page.reload();

            // Verify the bookmark persisted after reload
            await expect(page.getByRole('button', { name: 'Bookmarked' })).toBeVisible();
        });
    });

    test('15.9: Bookmarks sidebar nav item works', async ({ authenticatedPage: page }) => {
        await page.goto('/dashboard');

        // Click "Bookmarks" link in the sidebar navigation
        await page.getByRole('link', { name: 'Bookmarks' }).click();

        // Verify navigation to /bookmarks
        await page.waitForURL(/\/bookmarks/);
        await expect(page).toHaveURL(/\/bookmarks/);
    });
});
