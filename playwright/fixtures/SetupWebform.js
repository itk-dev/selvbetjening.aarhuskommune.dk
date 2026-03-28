import { expect } from '@playwright/test';
import { GlobalSettings } from './GlobalSettings';

export class SetupWebform {
  constructor(page, runnerTimestamp) {
    this.page = page;
    this.webformName = GlobalSettings.webformName + runnerTimestamp;
    this.titleInput = this.page.locator('div.js-form-item-title > input[name="title"]');
    this.createWebformButton = this.page.locator('div.form-actions > button.form-submit');
  }

  async goto() {
    await this.page.goto('/da/admin/structure/webform');
    await expect(this.page.locator('#block-gin-page-title > h1')).toHaveText('Webformularer');
  }

  async openCreateNewWebformDialog(title) {
    await expect(this.page.locator('a[href="/da/admin/structure/webform/add"]')).toBeVisible();
    await this.page.click('a[href="/da/admin/structure/webform/add"]');
    await expect(this.page.locator('div[role="dialog"].ui-dialog')).toBeVisible();
  }

  async createNewWebform() {
    await this.titleInput.fill(this.webformName);
    await this.createWebformButton.click();
    await expect(this.page.locator('div#block-gin-page-title > h1')).toHaveText(this.webformName);
  }
}
