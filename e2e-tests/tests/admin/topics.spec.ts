import { test, expect } from '../fixtures/auth';

test.describe('Admin Topic Management', () => {
  test('3.4: admin can access topic management', async ({ adminPage: page }) => {
    await page.goto('/admin/topics');

    await expect(page.getByRole('heading', { name: 'Topics' })).toBeVisible();
    await expect(page.getByText('Manage forum discussion topics')).toBeVisible();

    // Verify the topic table is visible with expected columns
    await expect(page.getByRole('columnheader', { name: 'Title' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Slug' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Visibility' })).toBeVisible();

    // Verify seeded topics appear
    await expect(page.getByText('General Discussion')).toBeVisible();
  });

  test('3.5: admin can create a topic', async ({ adminPage: page }) => {
    await page.goto('/admin/topics');

    await page.getByRole('link', { name: 'New Topic' }).click();

    // Verify we are on the create form
    await expect(page.getByRole('heading', { name: 'Create Topic' })).toBeVisible();

    // Fill the form
    await page.getByLabel('Title').fill('E2E Test Topic');
    await page.locator('#description').fill('A topic created by E2E tests');

    // Select visibility
    await page.getByRole('combobox').click();
    await page.getByRole('option', { name: 'Public' }).click();

    // Submit
    await page.getByRole('button', { name: 'Create Topic' }).click();

    // Verify redirect back to index with new topic visible
    await expect(page.getByText('E2E Test Topic')).toBeVisible();
  });

  test('3.6: admin can edit a topic', async ({ adminPage: page }) => {
    await page.goto('/admin/topics');

    // Click the edit link on the "General Discussion" row
    const row = page.getByRole('row').filter({ hasText: 'general-discussion' });
    await row.getByRole('link').first().click();

    // Verify edit form loads
    await expect(page.getByRole('heading', { name: 'Edit Topic' })).toBeVisible();

    // Modify the description (a safe change that won't break other tests)
    const descInput = page.getByLabel('Description');
    await descInput.clear();
    await descInput.fill('Updated description via E2E test');

    // Save
    await page.getByRole('button', { name: 'Update Topic' }).click();

    // Verify redirect back to the topics list
    await expect(page.getByRole('heading', { name: 'Topics' })).toBeVisible();
    await expect(page.getByText('General Discussion')).toBeVisible();
  });

  test('3.7: non-admin cannot access admin pages', async ({ authenticatedPage: page }) => {
    await page.goto('/admin/topics');

    // Regular user should see a 403 Forbidden response
    await expect(page.getByText('403')).toBeVisible();
    await expect(page.getByText('Forbidden')).toBeVisible();
  });

  test('11.1: admin topic form has icon picker', async ({ adminPage: page }) => {
    await page.goto('/admin/topics');

    await page.getByRole('link', { name: 'New Topic' }).click();

    // Verify icon picker button is visible on the create form
    const iconPickerButton = page.getByRole('button', { name: /select an icon/i });
    await expect(iconPickerButton).toBeVisible();

    // Click to open the icon picker dialog
    await iconPickerButton.click();

    // Verify the dialog opens with icon grid
    await expect(page.getByRole('heading', { name: 'Select Icon' })).toBeVisible();
    await expect(page.getByPlaceholder('Search icons...')).toBeVisible();

    // Verify icons are rendered in the grid
    const iconButtons = page.locator('[role="dialog"] button[title]');
    const count = await iconButtons.count();
    expect(count).toBeGreaterThan(0);
  });
});
