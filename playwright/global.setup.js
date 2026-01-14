// @ts-check
import { test as baseTest, expect } from '@playwright/test';
import { GlobalSettings } from './fixtures/GlobalSettings';

const test = baseTest.extend({
  authenticatedContext: async ({ browser }, use) => {
    // Create a new browser context
    const context = await browser.newContext();
    const page = await context.newPage();
    const affiliation = GlobalSettings.affiliationName;

    try {
      // Perform login
      await page.goto('/da/user/login');
      await page.fill('input[name="name"]', 'playwright');
      await page.fill('input[name="pass"]', 'playwrightt3st01');
      await page.click('input[name="op"]');

      // Ensure login is successful
      await expect(page.locator('h1.page-title').filter({hasText: 'playwright'})).toBeVisible();
      console.log('Successfully logged in.');

      // Navigate to overview and check if affiliation exists
      await page.goto('da/admin/structure/taxonomy/manage/user_affiliation/overview');
      const affiliationExists = await page.locator('table#taxonomy a').filter({ hasText: affiliation }).isVisible();

      // Create an affiliation if it does not exist
      if (!affiliationExists) {
        await page.goto('da/admin/structure/taxonomy/manage/user_affiliation/add');
        await page.fill('input[name="name[0][value]"]', affiliation);
        await page.click('input[data-drupal-selector="edit-overview"]');

        // Verify creation of affiliation
        await expect(page.locator('table#taxonomy a').filter({ hasText: affiliation })).toBeVisible();
        console.log('Successfully created affiliation.');
      } else {
        console.log('Affiliation already exists.');
      }

      // Assign affiliation to user
      await page.goto('da/admin/people');
      await expect(page.locator('td.views-field-name > a').filter({hasText: 'playwright'})).toBeVisible();
      await expect(page.locator('td.views-field-name > a').filter({hasText: 'playwright'})).toHaveAttribute('href');
      const userUrl = await page.locator('td.views-field-name > a').filter({hasText: 'playwright'}).getAttribute('href');

      await page.goto(userUrl + '/edit?destination=/da/admin/people');

      await expect(page.locator('div.js-form-item-terms-user-affiliation > select#edit-terms-user-affiliation > option').filter({hasText: affiliation})).toBeDefined();
      await page.locator('div.js-form-item-terms-user-affiliation > select#edit-terms-user-affiliation > option').filter({hasText: affiliation}).click();
      await page.locator('div#edit-actions > input#edit-submit').click();

      console.log('Successfully assigned affiliation.');
      // Provide the authenticated context to dependent tests
      await use(context);
    } finally {
      console.log('Basic setup complete.');
    }
  },
});

test('Global setup - Create authenticated context', async ({ authenticatedContext }) => {
  console.log('Authenticated context is ready for dependent tests.');
});

module.exports = { test };
