<?php

namespace Drupal\webform_revisions;

use Drupal\webform\Plugin\WebformElementManager;

/**
 * Overrides for the WebformElementManager class for revisions support.
 */
class WebformRevisionsElementManager extends WebformElementManager {

  /**
   * Reset the plugin instances.
   *
   * Used when loading a new webform revision.
   */
  public function resetPluginInstances() {
    $this->instances = [];
  }

}
