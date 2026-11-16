<?php

namespace Drupal\config_entity_revisions\Entity\Handler;

use Drupal\config_entity_revisions\ConfigEntityRevisionsStorageInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Storage class overrides for config entity revisions.
 *
 * @package Drupal\config_entity_revisions\Entity
 */
class ConfigEntityRevisionsStorage extends SqlContentEntityStorage implements ConfigEntityRevisionsStorageInterface {

  /**
   * Gets the latest published revision ID of the entity.
   *
   * @param int $config_entity_id
   *   The config entity ID to match.
   *
   * @return int
   *   The identifier of the latest published revision of the entity, or NULL
   *   if the entity does not have a published revision.
   */
  public function getLatestPublishedRevisionId($config_entity_id) {
    $revision = $this->database->select("config_entity_revisions_revision", 'c')
      ->fields('c', ['revision'])
      ->condition($this->entityType->getKey('id'), $config_entity_id)
      ->condition('published', TRUE)
      ->orderby('revision', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchField();

    return $revision;
  }

  /**
   * Gets the latest published revision ID of the entity.
   *
   * @param int $config_entity_id
   *   The config entity ID to match.
   *
   * @return int
   *   The identifier of the latest published revision of the entity, or NULL
   *   if the entity does not have a published revision.
   */
  public function getLatestPublishedRevision($config_entity_id) {
    $revision = NULL;
    $revision_id = $this->getLatestPublishedRevisionId($config_entity_id);
    if ($revision_id) {
      $revision = $this->loadRevision($revision_id);
    }

    return $revision;
  }

  /**
   * Gets the latest revision ID of the entity.
   *
   * @param int $config_entity_id
   *   The config entity ID to match.
   *
   * @return int
   *   The identifier of the latest published revision of the entity, or NULL
   *   if the entity does not have a published revision.
   */
  public function getLatestRevision($config_entity_id) {
    $revision = NULL;
    $revision_id = $this->database->select("config_entity_revisions_revision", 'c')
      ->fields('c', ['revision'])
      ->condition($this->entityType->getKey('id'), $config_entity_id)
      ->orderby('revision', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchField();

    if ($revision_id) {
      $revision = $this->loadRevision($revision_id);
    }

    return $revision;
  }

}
