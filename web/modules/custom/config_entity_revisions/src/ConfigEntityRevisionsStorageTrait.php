<?php

namespace Drupal\config_entity_revisions;

/**
 * Defines a trait for modules to use in creating a revisioned config entity.
 */
trait ConfigEntityRevisionsStorageTrait {

  /**
   * Get the name of the module implementing the API.
   *
   * @return string
   *   The name of the module implementing the API.
   */
  public function moduleName() {
    return $this->constants['module_name'];
  }

  /**
   * The name of the entity being revisioned.
   *
   * @return string
   *   The name of the entity being revisioned.
   */
  public function configEntityName() {
    return $this->constants['config_entity_name'];
  }

  /**
   * The name of the content entity in which revisions are being stored.
   *
   * @return string
   *   The name of the content entity in which revisions are being stored.
   */
  public function revisionsEntityName() {
    return $this->constants['revisions_entity_name'];
  }

  /**
   * The name of the content entity id field on the config entity.
   *
   * @return string
   *   The name of the content entity id field on the config entity.
   */
  public function settingName() {
    return $this->constants['setting_name'];
  }

  /**
   * The label for the module implementing the API.
   *
   * @return string
   *   The label for the module implementing the API.
   */
  public function title() {
    return $this->constants['title'];
  }

  /**
   * Does the config entity have its own content entities?
   *
   * @return bool
   *   Does the config entity have its own content entities?
   */
  public function hasOwnContent() {
    return $this->constants['has_own_content'];
  }

  /**
   * The name of the content entities that the config entity has.
   *
   * @return string
   *   The name of the content entities that the config entity has.
   */
  public function contentEntityType() {
    return $this->constants['content_entity_type'];
  }

  /**
   * The parameter name for the related content type (eg webform submissions).
   *
   * @return string
   *   The machine name of the related content type.
   */
  public function contentParameterName() {
    return $this->constants['content_parameter_name'];
  }

  /**
   * The reference field on content entities to the config entity.
   *
   * @return string
   *   The reference field for content entities.
   */
  public function contentParentReferenceField() {
    return $this->constants['content_parent_reference_field'];
  }

  /**
   * The admin permission required to edit the config entity.
   *
   * @return string
   *   The admin permission required to edit the config entity.
   */
  public function adminPermission() {
    return $this->constants['admin_permission'];
  }

  /**
   * Whether the entity has a canonical URL.
   *
   * @return bool
   *   Whether the entity has a canonical URL.
   */
  public function hasCanonicalUrl() {
    return $this->constants['has_canonical_url'] ?: FALSE;
  }

  /**
   * Common calls for loading a revision.
   *
   * @param int $revisionId
   *   The revision ID being loaded.
   */
  private function doLoadRevision($revisionId) {
    $revisionsEntity = $this->entityTypeManager->getStorage('config_entity_revisions')
      ->loadRevision($revisionId);
    $entity = NULL;

    if ($revisionsEntity) {
      $entity = \Drupal::getContainer()->get('serializer')->deserialize(
        $revisionsEntity->get('configuration')->value, $this->getEntityClass(),
        'json');

      $entity->set('enforceIsNew', FALSE);
      $entity->setThirdPartySetting('webform_revisions', 'revisionId', $revisionId);
      $loaded = [$entity];
      $entity::postLoad($this, $loaded);
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision($revision_id) {
    $this->entityTypeManager->getStorage('config_entity_revisions')
      ->deleteRevision($revision_id);
  }

}
