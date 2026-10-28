<?php

namespace Drupal\config_entity_revisions;

use Drupal\Core\Url;

/**
 * Trait for adding CER specific methods to an entity.
 */
trait ConfigEntityRevisionsConfigTrait {

  /**
   * Restore the entity type manager after deserialisation.
   */
  public function __wakeup(): void {
    $this->entityTypeManager = \Drupal::service('entity_type.manager');
  }

  /**
   * Gets the revision identifier of the entity.
   *
   * @return int
   *   The revision identifier of the entity, or NULL if the entity does not
   *   have a revision identifier.
   */
  public function getRevisionId() {
    return $this->getThirdPartySetting($this->moduleName(), 'revisionId');
  }

  /**
   * Set revision ID.
   *
   * @param int $revisionID
   *   The revision ID that this class instance represents.
   */
  public function setRevisionId($revisionID) {
    $this->setThirdPartySetting($this->moduleName(), 'revisionId', $revisionID);
  }

  /**
   * Get the config entity storage.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   *   The storage for the config entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function configEntityStorage() {
    return $this->entityTypeManager->getStorage($this->configEntityName());
  }

  /**
   * Get the revisions entity storage.
   *
   * @return \Drupal\Core\Entity\ContentEntityStorageInterface
   *   The storage for the revisions entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function contentEntityStorage() {
    return $this->entityTypeManager->getStorage('config_entity_revisions');
  }

  /**
   * Default revision of revisions entity that matches the config entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The matching entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getContentEntity() {
    $contentEntityID = $this->getContentEntityId();

    if (!$contentEntityID) {
      return NULL;
    }

    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->contentEntityStorage();

    // Get the matching revision ID if one is provided.
    if ($this->getRevisionId()) {
      return $storage->loadRevision($this->getRevisionId());
    }

    // Otherwise, just get the default revision.
    return $storage->load($contentEntityID);
  }

  /**
   * Get the number of revisions for this config entity.
   */
  public function getNumberOfRevisions() {
    $contentEntityID = $this->getContentEntityId();

    if (!$contentEntityID) {
      return 0;
    }

    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $revisions = $this->contentEntityStorage()->getQuery()
      ->condition($this->getEntityType()->getKey('id'), $contentEntityID)
      ->allRevisions()
      ->accessCheck(FALSE)
      ->execute();
    return count($revisions);
  }

  /**
   * Link to this revision.
   */
  public function revisionLink($text) {
    $contentEntity = $this->getContentEntity();
    if ($this->hasCanonicalUrl() && $contentEntity->isDefaultRevision() && $contentEntity->isPublished()) {
      $link = $this->toLink($text)->toString();
    }
    else {
      $configEntityType = $this->getEntityType()->id();
      $url = new Url("entity.{$configEntityType}.revision", [
        $configEntityType => $this->id(),
        "revision_id" => $this->getRevisionId(),
      ]);
      $link = \Drupal::linkGenerator()->generate($text, $url);
    }

    return $link;
  }

  /**
   * Get the number of revisions for this config entity.
   */
  public function getEarlierRevisions() {
    $contentEntityID = $this->getContentEntityId();

    if (!$contentEntityID) {
      return [];
    }

    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $revisions = $this->contentEntityStorage()->getQuery()
      ->condition($this->getEntityType()->getKey('id'), $contentEntityID)
      ->condition('revision', $this->getRevisionId(), '<')
      ->allRevisions()
      ->accessCheck(FALSE)
      ->execute();
    return $this->contentEntityStorage()->loadMultiple(array_keys($revisions));
  }

  /**
   * Get the number of revisions for this config entity.
   */
  public function getLaterRevisions() {
    $contentEntityID = $this->getContentEntityId();

    if (!$contentEntityID) {
      return [];
    }

    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $revisions = $this->contentEntityStorage()->getQuery()
      ->condition($this->getEntityType()->getKey('id'), $contentEntityID)
      ->condition('revision', $this->getRevisionId(), '>')
      ->sort($this->getEntityType()->getKey('id'), 'DESC')
      ->allRevisions()
      ->accessCheck(FALSE)
      ->execute();
    return $this->contentEntityStorage()->loadMultipleRevisions(array_keys($revisions));
  }

}
