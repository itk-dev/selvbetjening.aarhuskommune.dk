<?php

namespace Drupal\views_revisions\Entity;

use Drupal\config_entity_revisions\ConfigEntityRevisionsConfigTrait;
use Drupal\config_entity_revisions\ConfigEntityRevisionsInterface;
use Drupal\views\Entity\View;
use Drupal\views_revisions\ViewsRevisionsConfigTrait;

/**
 * Revisions support for Views.
 */
class ViewsRevisions extends View implements ConfigEntityRevisionsInterface {

  use ViewsRevisionsConfigTrait, ConfigEntityRevisionsConfigTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  public $entityTypeManager;

  // Declare these fields so that they're put in the storage object instead of
  // the ViewsUI object during entity building. We can then access them in
  // createUpdateRevision.
  /**
   * The revision id.
   *
   * @var int revision
   */
  protected $revision;

  /**
   * The revision log message.
   *
   * @var array revision_log_message
   */
  protected $revision_log_message;

  /**
   * Constructs an Entity object.
   *
   * @param array $values
   *   An array of values to set, keyed by property name. If the entity type
   *   has bundles, the bundle key has to be specified.
   * @param string $entity_type
   *   The type of the entity to create.
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    $this->entityTypeManager = \Drupal::service('entity_type.manager');
  }

  /**
   * Set in the configEntity an identifier for the matching content entity.
   *
   * @param mixed $contentEntityID
   *   The ID used to match the content entity.
   */
  public function setContentEntityId($contentEntityID) {
    $this->setThirdPartySetting('views_revisions', 'contentEntity_id', $contentEntityID);
  }

  /**
   * Get from the configEntity the ID of the matching content entity.
   *
   * @return int|null
   *   The ID (if any) of the matching content entity.
   */
  public function getContentEntityId() {
    return $this->getThirdPartySetting('views_revisions', 'contentEntity_id');
  }

}
