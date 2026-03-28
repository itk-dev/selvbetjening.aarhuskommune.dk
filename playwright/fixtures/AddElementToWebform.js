import { expect } from '@playwright/test';
import { GlobalSettings } from './GlobalSettings';

export class AddElementToWebform {
  constructor(page, runnerTimestamp) {
    this.page = page;
    this.webformName = GlobalSettings.webformName + runnerTimestamp;
    this.titleInput = this.page.locator('div.js-form-item-properties-title > input[name="properties[title]"]');
  }

  async addElement(elementName) {
    await this.page.goto(`da/admin/structure/webform/manage/${this.webformName}/element/add/${elementName}`);
    await expect(this.page.locator('#block-gin-page-title > h1')).toHaveText('Tilføj Tekstfelt element');
    await this.titleInput.fill(elementName);
    await this.page.click('div.form-actions > input.form-submit');

    await expect(this.page.locator(`a#edit-webform-ui-elements-${elementName}-title-link`)).toBeVisible();
  }

}
