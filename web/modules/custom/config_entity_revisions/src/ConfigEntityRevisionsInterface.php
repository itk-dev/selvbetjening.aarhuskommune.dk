<?php

namespace Drupal\config_entity_revisions;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * ConfigEntityRevisionsInterface.
 *
 * Adds revision related fields to a configuration entity.
 */
interface ConfigEntityRevisionsInterface extends ConfigEntityInterface {

  /**
   * The name of the module implementing the API.
   *
   * @return string
   *   The name of the module implementing the API.
   */
  public function moduleName();

  /**
   * The name of the entity being revisioned.
   *
   * @return string
   *   The name of the entity being revisioned.
   */
  public function configEntityName();

  /**
   * The name of the content entity in which revisions are being stored.
   *
   * @return string
   *   The name of the content entity in which revisions are being stored.
   */
  public function revisionsEntityName();

  /**
   * The setting name on the config entity storing content entity ids.
   *
   * @return string
   *   The name of the setting on the config entity in which content entity
   *   ids are stored.
   */
  public function settingName();

  /**
   * The label for the module implementing the API.
   *
   * @return string
   *   The proper name (displayed to the user) of the module implementing the
   *   API.
   */
  public function title();

  /**
   * Does the config entity have its own content entities?
   *
   * @return bool
   *   Does the config entity have its own content entities?
   */
  public function hasOwnContent();

  /**
   * The name of the content entities that the config entity has.
   *
   * @return string
   *   The name of the content entities that the config entity has.
   */
  public function contentEntityType();

  /**
   * The parameter name for the related content type (eg webform submissions).
   *
   * @return string
   *   The machine name of the related content type.
   */
  public function contentParameterName();

  /**
   * The reference field on content entities to the config entity.
   *
   * @return string
   *   The reference field for content entities.
   */
  public function contentParentReferenceField();

  /**
   * The admin permission required to edit the config entity.
   *
   * @return string
   *   The admin permission required to edit the config entity.
   */
  public function adminPermission();

  /**
   * Get the config entity storage.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   *   The storage for the config entity.
   */
  public function configEntityStorage();

  /**
   * Get the revisions entity storage.
   *
   * @return \Drupal\Core\Entity\ContentEntityStorageInterface
   *   The storage for the revisions entity.
   */
  public function contentEntityStorage();

  /**
   * Set in the configEntity an identifier for the matching content entity.
   *
   * @param mixed $contentEntityID
   *   The ID used to match the content entity.
   */
  public function setContentEntityId($contentEntityID);

  /**
   * Get from the configEntity the ID of the matching content entity.
   *
   * @return int|null
   *   The ID (if any) of the matching content entity.
   */
  public function getContentEntityId();

  /**
   * Default revision of revisions entity that matches the config entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The matching entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getContentEntity();

  /**
   * Gets the revision identifier of the entity.
   *
   * @return int
   *   The revision identifier of the entity, or NULL if the entity does not
   *   have a revision identifier.
   */
  public function getRevisionId();

  /**
   * Set revision ID.
   *
   * @param int $revisionID
   *   The revision ID that this class instance represents.
   */
  public function setRevisionId($revisionID);

}
