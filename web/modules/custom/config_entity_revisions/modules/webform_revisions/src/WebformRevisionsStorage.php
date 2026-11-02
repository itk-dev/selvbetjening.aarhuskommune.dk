<?php

namespace Drupal\webform_revisions;

use Drupal\config_entity_revisions\ConfigEntityRevisionsStorageTrait;
use Drupal\webform\WebformEntityStorage;

/**
 * Webform revisions storage class overrides.
 */
class WebformRevisionsStorage extends WebformEntityStorage {
  use ConfigEntityRevisionsStorageTrait;

  /**
   * Load a revision of a webform.
   *
   * @param int $revision_id
   *   The revision to be loaded.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The loaded revision.
   */
  public function loadRevision($revision_id) {
    return $this->doLoadRevision($revision_id);
  }

}
