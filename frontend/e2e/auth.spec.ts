import { expect, test } from '@playwright/test';

test('unauthenticated visitor is redirected to the login page', async ({ page }) => {
  await page.goto('/');

  await expect(page).toHaveURL(/\/login/);
  await expect(page.getByRole('heading', { name: 'Transport Manager' })).toBeVisible();
  await expect(page.getByText('Sign in to your account')).toBeVisible();
});

test('login form rejects empty submission', async ({ page }) => {
  await page.goto('/login');

  const submit = page.getByRole('button', { name: 'Sign in' });
  await submit.click();

  await expect(page).toHaveURL(/\/login/);
});
