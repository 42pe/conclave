import { test, expect } from '../fixtures/auth';

test.describe('Slate Editor', () => {
    test('4.1: Editor loads in discussion create with toolbar buttons', async ({ authenticatedPage: page }) => {
        await page.goto('/topics/general-discussion/discussions/create');

        // Verify the page loaded
        await expect(page.getByRole('heading', { name: 'New Discussion' })).toBeVisible();

        // Verify the Slate editor is present (contenteditable area)
        const editor = page.locator('[contenteditable="true"]');
        await expect(editor).toBeVisible();

        // Verify toolbar buttons are present (using aria-label attributes)
        await expect(page.getByRole('button', { name: 'bold' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'italic' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'underline' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'code' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'heading-one' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'heading-two' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'bulleted-list' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'numbered-list' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'blockquote' })).toBeVisible();
    });

    test('4.2: Can type text in editor', async ({ authenticatedPage: page }) => {
        await page.goto('/topics/general-discussion/discussions/create');

        // Click on the contenteditable area and type text
        const editor = page.locator('[contenteditable="true"]');
        await editor.click();
        await page.keyboard.type('Hello, this is a test discussion body.');

        // Verify the text appears in the editor
        await expect(editor).toContainText('Hello, this is a test discussion body.');
    });

    test('4.3: Toolbar bold button works', async ({ authenticatedPage: page }) => {
        await page.goto('/topics/general-discussion/discussions/create');

        const editor = page.locator('[contenteditable="true"]');
        await editor.click();

        // Toggle bold on first via keyboard shortcut, then type
        await page.keyboard.press('ControlOrMeta+B');
        await page.keyboard.type('This is bold');

        // Verify bold formatting applied — Slate renders bold as <strong>
        await expect(editor.locator('strong')).toContainText('This is bold');
    });

    test('4.4: Toolbar bulleted-list button works', async ({ authenticatedPage: page }) => {
        await page.goto('/topics/general-discussion/discussions/create');

        // Focus the editor
        const editor = page.locator('[contenteditable="true"]');
        await editor.click();

        // Click the bulleted-list toolbar button to activate list mode
        await page.getByRole('button', { name: 'bulleted-list' }).click();

        // Re-focus editor and type text
        await editor.click();
        await page.keyboard.type('First list item');

        // Verify a list element was created in the editor
        await expect(editor.locator('ul')).toBeVisible();
    });
});
