import { test, expect } from '@playwright/test';
import { LoginPage } from './pages/LoginPage';

test.describe('Authentication', () => {
    test('admin can log in and reach the dashboard', async ({ page }) => {
        const loginPage = new LoginPage(page);

        await loginPage.goto();
        await loginPage.login('admin@flow-ledger.test', 'password');

        await expect(page).not.toHaveURL(/login/);
        await expect(page).toHaveURL('https://main.flow-ledger.test/');
    });

    test('shows error for invalid credentials', async ({ page }) => {
        const loginPage = new LoginPage(page);

        await loginPage.goto();
        await loginPage.login('admin@flow-ledger.test', 'wrong-password');

        await expect(page).toHaveURL(/login/);
        await expect(page.locator('.kt-alert-destructive')).toBeVisible();
    });
});
