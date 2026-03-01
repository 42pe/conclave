import { test, expect } from '../fixtures/auth';
import path from 'path';
import fs from 'fs';

// Create a minimal 1x1 red PNG for testing (68 bytes)
function createTestImage(): string {
    const testImagePath = path.join(__dirname, '..', 'fixtures', 'test-avatar.png');

    if (!fs.existsSync(testImagePath)) {
        // Minimal valid PNG: 1x1 pixel, red
        const pngBuffer = Buffer.from(
            '89504e470d0a1a0a0000000d4948445200000001000000010802000000907753de0000000c4944415408d763f8cfc000000002000198e1d2a50000000049454e44ae426082',
            'hex',
        );
        fs.writeFileSync(testImagePath, pngBuffer);
    }

    return testImagePath;
}

test.describe('Avatar Upload', () => {
    test('2.1: Can upload avatar on profile settings', async ({ authenticatedPage: page }) => {
        const testImagePath = createTestImage();

        await page.goto('/settings/profile');

        // Find the file input for avatar upload
        const fileInput = page.locator('input[type="file"][accept*="image"]').first();
        await expect(fileInput).toBeAttached();

        // Upload the test image
        await fileInput.setInputFiles(testImagePath);

        // Click the Upload button
        await page.getByRole('button', { name: 'Upload' }).click();

        // Wait for the upload to complete — the avatar image should appear
        // The avatar-upload component renders an <img> tag when avatarPath is set
        const avatarImage = page.locator('img[alt]').first();
        await expect(avatarImage).toBeVisible({ timeout: 10000 });
    });
});
