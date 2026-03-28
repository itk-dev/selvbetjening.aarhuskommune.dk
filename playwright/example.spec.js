// @ts-check
import { test, expect } from '@playwright/test';
import { SetupWebform } from './fixtures/SetupWebform';
import { AddElementToWebform } from './fixtures/AddElementToWebform';

const runnerTimestamp = new Date().getTime();

test.describe('Login flow, create webform', () => {
  let authenticatedContext;

  test.beforeAll('1. Login', async ({ browser }) => {

    const context = await browser.newContext();
    const page = await context.newPage();

    await page.goto('/da/user/login');

    await expect(page.locator('input[name="name"]')).toBeVisible();
    await page.fill('input[name="name"]', 'playwright');
    await page.fill('input[name="pass"]', 'playwrightt3st01');

    await page.click('input[name="op"]');

    await expect(page.locator('#block-gin-page-title > h1')).toHaveText('playwright');

    authenticatedContext = context;

    await page.close();
  });

  test('2. Create new webform', async () => {
    const page = await authenticatedContext.newPage();
    const webform = new SetupWebform(page, runnerTimestamp);

    await webform.goto();
    await webform.openCreateNewWebformDialog();
    await webform.createNewWebform();
  })

  test('3. Configure textfield element', async ({} , testInfo) => {
    const page = await authenticatedContext.newPage();
    const addElementToWebform = new AddElementToWebform(page, runnerTimestamp);

    await addElementToWebform.addElement('textfield');

    const customersScreenshot = await page.screenshot();
    await testInfo.attach('Customers Page', {
      body: customersScreenshot,
      contentType: 'image/png',
    });

  })

});
