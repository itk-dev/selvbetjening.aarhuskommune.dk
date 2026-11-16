<?php

namespace Drupal\config_entity_revisions;

use Drupal\content_moderation\ModerationInformation;
use Drupal\Core\Entity\EntityInterface;

/**
 * Override ModerationInformation for config entity support.
 */
class ConfigEntityRevisionsModerationInformation extends ModerationInformation {

  /**
   * {@inheritdoc}
   */
  public function isModeratedEntity(EntityInterface $entity) {
    if (!$this->shouldModerateEntitiesOfBundle($entity->getEntityType(), $entity->bundle())) {
      return FALSE;
    }
    return $this->entityTypeManager->getHandler($entity->getEntityTypeId(), 'moderation')->isModeratedEntity($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function hasPendingRevision(EntityInterface $entity) {
    $result = FALSE;
    if ($this->isModeratedEntity($entity)) {
      /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
      $latest_revision_id = $storage->getLatestTranslationAffectedRevisionId($entity->id(), $entity->language()->getId());
      $default_revision_id = $entity->isDefaultRevision() && !$entity->isNewRevision() && ($revision_id = $entity->getRevisionId()) ?
        $revision_id : $this->getDefaultRevisionId($entity->getEntityTypeId(), $entity->id());
      if ($latest_revision_id !== NULL && $latest_revision_id != $default_revision_id) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $latest_revision */
        $latest_revision = $storage->loadRevision($latest_revision_id);
        $result = !$latest_revision->wasDefaultRevision();
      }
    }
    return $result;
  }

}
