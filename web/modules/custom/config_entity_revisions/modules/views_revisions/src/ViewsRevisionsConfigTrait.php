<?php

namespace Drupal\views_revisions;

use Drupal\config_entity_revisions\ConfigEntityRevisionsStorageTrait;

/**
 * Views Revisions config trait.
 */
trait ViewsRevisionsConfigTrait {
  use ConfigEntityRevisionsStorageTrait;

  /**
   * Constants for views revisions.
   *
   * @var array
   */
  private $constants = [
    'module_name' => 'views_revisions',
    'config_entity_name' => 'view',
    'revisions_entity_name' => 'ViewsRevisions',
    'setting_name' => 'views_revisions_id',
    'title' => 'Views',
    'has_own_content' => FALSE,
    'admin_permission' => 'administer views',
    'has_canonical_url' => FALSE,
  ];

  /**
   * Get the entity that actually has revisions.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity that has revisions.
   */
  public function revisionedEntity() {
    return $this->get('storage');
  }

}
