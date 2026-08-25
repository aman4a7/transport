import { expect, test } from '@playwright/test';

const API_URL = process.env.E2E_API_URL ?? 'http://localhost:8000';
const EMAIL = process.env.E2E_EMAIL;
const PASSWORD = process.env.E2E_PASSWORD;

test('authenticated user lands on the dashboard', async ({ page, request }) => {
  test.skip(!EMAIL || !PASSWORD, 'E2E_EMAIL and E2E_PASSWORD are not configured.');

  const health = await request.get(`${API_URL}/api/v1/health`).catch(() => null);
  test.skip(!health || !health.ok(), 'Backend API is not reachable.');

  await page.goto('/login');
  await page.locator('#email').fill(EMAIL as string);
  await page.locator('#password').fill(PASSWORD as string);
  await page.getByRole('button', { name: 'Sign in' }).click();

  await expect(page).toHaveURL(/\/app\/dashboard/, { timeout: 15000 });
});
