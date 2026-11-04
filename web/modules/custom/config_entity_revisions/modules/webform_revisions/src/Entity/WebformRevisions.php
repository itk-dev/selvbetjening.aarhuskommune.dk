<?php

namespace Drupal\webform_revisions\Entity;

use Drupal\config_entity_revisions\ConfigEntityRevisionsConfigTrait;
use Drupal\config_entity_revisions\ConfigEntityRevisionsInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform_revisions\Controller\WebformRevisionsController;
use Drupal\webform_revisions\WebformRevisionsConfigTrait;

/**
 * Overrides for the Webform class to add revisions support.
 */
class WebformRevisions extends Webform implements ConfigEntityRevisionsInterface {

  use WebformRevisionsConfigTrait, ConfigEntityRevisionsConfigTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $entityTypeManager;

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
    $this->setThirdPartySetting('webform_revisions', 'contentEntity_id', $contentEntityID);
  }

  /**
   * Get from the configEntity the ID of the matching content entity.
   *
   * @return int|null
   *   The ID (if any) of the matching content entity.
   */
  public function getContentEntityId() {
    static $checked = FALSE;

    $id = $this->getThirdPartySetting('webform_revisions', 'contentEntity_id');

    // Issue 3051713 - validate that the record exists.
    if ($id && !$checked) {
      $checked = TRUE;
      $record = $this->entityTypeManager->getStorage('config_entity_revisions')
        ->load($id);
      if (!$record) {
        $id = NULL;
      }
    }

    return $id;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteElement($key) {
    // Delete element from the elements render array.
    $elements = $this->getElementsDecoded();
    $this->deleteElementRecursive($elements, $key);
    $this->setElements($elements);

    // Don't delete submission data so that it can still be viewed for previous
    // revisions.
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmissionForm(array $values = [], $operation = 'add') {
    // If invoked by WebformEntityController->addForm, ensure the webform
    // revision used is influenced by the path.
    if (empty($values) && $operation == 'add') {
      $values['webform'] = $this;
    }

    return parent::getSubmissionForm($values, $operation);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    /** @var ConfigEntityRevisionsControllerInterface $revisionsController */
    $revisionsController = WebformRevisionsController::create(\Drupal::getContainer());
    $originalContentEntity = $this->getContentEntityId();
    $contentEntity = $revisionsController->createUpdateRevision($this);
    if (!$originalContentEntity) {
      // Update any existing submissions to point at the original revision.
      \Drupal::database()
        ->query('UPDATE {webform_submission} SET webform_revision = :rid WHERE webform_id = :form',
          [':rid' => $contentEntity->getRevisionID(), ':form' => $this->id()]);
    }

    // Webform submissions get statically cached with a copy of the webform
    // in their value data. That prevents any changes to the webform being
    // visible immediately if (eg) the webform display is modified.
    // We therefore get the IDs of submissions related to the webform just
    // saved and remove them from the cache.
    $submissionStorage = $this->entityTypeManager->getStorage('webform_submission');
    $query = $submissionStorage->getQuery()
      ->condition('webform_id', $this->id())
      ->accessCheck(FALSE);
    $submissionIds = $query->execute();
    $submissionStorage->resetCache($submissionIds);

    parent::postSave($storage, $update);
  }

}
