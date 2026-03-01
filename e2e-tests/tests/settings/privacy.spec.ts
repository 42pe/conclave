import { test, expect } from '../fixtures/auth';

test.describe('Privacy Settings', () => {
    test('2.4: Privacy settings page loads with toggle switches', async ({ authenticatedPage: page }) => {
        await page.goto('/settings/privacy');

        // Verify the page heading is present (use role to avoid strict mode)
        await expect(page.getByRole('heading', { name: 'Privacy settings', exact: true })).toBeVisible();
        await expect(page.getByText('Control what information is visible on your profile')).toBeVisible();

        // Verify all three toggle switches are present
        const showRealNameSwitch = page.locator('#show_real_name');
        const showEmailSwitch = page.locator('#show_email');
        const showInDirectorySwitch = page.locator('#show_in_directory');

        await expect(showRealNameSwitch).toBeVisible();
        await expect(showEmailSwitch).toBeVisible();
        await expect(showInDirectorySwitch).toBeVisible();

        // Verify each switch has the correct role
        await expect(showRealNameSwitch).toHaveRole('switch');
        await expect(showEmailSwitch).toHaveRole('switch');
        await expect(showInDirectorySwitch).toHaveRole('switch');

        // Verify labels are visible
        await expect(page.getByText('Show your real name on your profile')).toBeVisible();
        await expect(page.getByText('Show your email address on your profile')).toBeVisible();
        await expect(page.getByText('Appear in the user directory')).toBeVisible();

        // Verify Save button exists
        await expect(page.getByRole('button', { name: 'Save' })).toBeVisible();
    });

    test('2.5: Privacy settings persist after toggle and save', async ({ authenticatedPage: page }) => {
        await page.goto('/settings/privacy');

        // Get the current state of the "show_in_directory" switch
        const showInDirectorySwitch = page.locator('#show_in_directory');
        const initialState = await showInDirectorySwitch.getAttribute('data-state');

        // Toggle the switch
        await showInDirectorySwitch.click();

        // Verify the switch state changed
        const expectedNewState = initialState === 'checked' ? 'unchecked' : 'checked';
        await expect(showInDirectorySwitch).toHaveAttribute('data-state', expectedNewState);

        // Save the form
        await page.getByRole('button', { name: 'Save' }).click();

        // Wait for the "Saved" confirmation to appear
        await expect(page.getByText('Saved')).toBeVisible();

        // Reload the page
        await page.reload();

        // Verify the switch state persisted
        const reloadedSwitch = page.locator('#show_in_directory');
        await expect(reloadedSwitch).toHaveAttribute('data-state', expectedNewState);
    });
});
