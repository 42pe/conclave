import { test, expect } from '../fixtures/auth';

test.describe('Notification Preferences', () => {
    test('10.2: Notification preferences page accessible with toggle switches', async ({ authenticatedPage: page }) => {
        await page.goto('/settings/notifications');

        // Verify the page heading
        await expect(page.getByText('Email notification settings')).toBeVisible();
        await expect(
            page.getByText('Choose which email notifications you receive'),
        ).toBeVisible();

        // Verify all three notification toggle switches are present
        const replySwitch = page.locator('#notify_replies');
        const messageSwitch = page.locator('#notify_messages');
        const mentionSwitch = page.locator('#notify_mentions');

        await expect(replySwitch).toBeVisible();
        await expect(messageSwitch).toBeVisible();
        await expect(mentionSwitch).toBeVisible();

        // Verify each switch has the correct role
        await expect(replySwitch).toHaveRole('switch');
        await expect(messageSwitch).toHaveRole('switch');
        await expect(mentionSwitch).toHaveRole('switch');

        // Verify labels are visible
        await expect(page.getByText('Email on new replies')).toBeVisible();
        await expect(page.getByText('Email on new messages')).toBeVisible();
        await expect(page.getByText('Email on mentions')).toBeVisible();

        // Verify Save button exists
        await expect(page.getByRole('button', { name: 'Save' })).toBeVisible();
    });
});

test.describe('Notification Panel', () => {
    test('13.1: Bell icon visible in sidebar', async ({ authenticatedPage: page }) => {
        // The sidebar is rendered on any authenticated page
        await page.goto('/dashboard');

        // The NotificationPanel renders a SidebarMenuButton with text "Notifications"
        const notificationsButton = page.getByRole('button', { name: 'Notifications' });
        await expect(notificationsButton).toBeVisible();
    });

    test('13.2: Notification panel opens on click', async ({ authenticatedPage: page }) => {
        await page.goto('/dashboard');

        // Click the Notifications button in the sidebar
        const notificationsButton = page.getByRole('button', { name: 'Notifications' });
        await notificationsButton.click();

        // The Sheet component opens — verify the sheet content is visible
        // The SheetTitle reads "Notifications"
        const sheetTitle = page.getByRole('heading', { name: 'Notifications' });
        await expect(sheetTitle).toBeVisible();

        // The SheetDescription (sr-only) should also be present
        await expect(page.getByText('Your recent notifications')).toBeAttached();
    });

    test('13.10: Notification panel shows empty state when no notifications', async ({ authenticatedPage: page }) => {
        await page.goto('/dashboard');

        // Open the notification panel
        const notificationsButton = page.getByRole('button', { name: 'Notifications' });
        await notificationsButton.click();

        // Wait for the panel to open and loading to finish
        await expect(page.getByRole('heading', { name: 'Notifications' })).toBeVisible();

        // Verify the empty state message appears (after loading spinner disappears)
        await expect(page.getByText('No notifications yet')).toBeVisible({ timeout: 10000 });
    });
});
