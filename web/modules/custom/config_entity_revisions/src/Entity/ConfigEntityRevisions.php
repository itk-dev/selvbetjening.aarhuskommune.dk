<?php

namespace Drupal\config_entity_revisions\Entity;

use Drupal\config_entity_revisions\ConfigEntityRevisionsEntityInterface;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the config entity revision entity class.
 *
 * @ContentEntityType(
 *   id = "config_entity_revisions",
 *   label = @Translation("Config Entity Revisions"),
 *   label_collection = @Translation("Config Entity revisions"),
 *   label_singular = @Translation("Config Entity revision"),
 *   label_plural = @Translation("Config Entity revisions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Config Entity revision",
 *     plural = "@count Config Entity revisions"
 *   ),
 *   bundle_label = @Translation("Config Entity with revisions"),
 *   handlers = {
 *     "moderation" = "Drupal\config_entity_revisions\Entity\Handler\ConfigEntityRevisionsModerationHandler",
 *     "storage" = "Drupal\config_entity_revisions\Entity\Handler\ConfigEntityRevisionsStorage",
 *     "form" = {
 *       "default" = "Drupal\config_entity_revisions\ConfigEntityRevisionForm",
 *       "delete" = "Drupal\config_entity_revisions\Form\ConfigEntityRevisionDeleteForm",
 *       "edit" = "Drupal\config_entity_revisions\ConfigEntityRevisionForm"
 *     },
 *   },
 *   base_table = "config_entity_revisions",
 *   data_table = "config_entity_revisions_field_data",
 *   revision_table = "config_entity_revisions_revision",
 *   revision_data_table = "config_entity_revisions_revision_data",
 *   moderation = "Drupal\config_entity_revisions\Entity\Handler\ConfigEntityRevisionsModerationHandler",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision",
 *     "bundle" = "type",
 *     "name" = "name",
 *     "uid" = "uid",
 *     "uuid" = "uuid",
 *     "published" = "published",
 *   },
 *   bundle_entity_type = "config_entity_revisions_type",
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message",
 *   },
 *   permission_granularity = "entity_type",
 *   admin_permission = "administer config_entity_revisions",
 *   links = {
 *     "add-page" = "/config_entity_revisions/add",
 *     "add-form" = "/config_entity_revisions/add/{config_entity_revisions}",
 *     "canonical" = "/config_entity_revisions/{config_entity_revisions}",
 *     "delete-form" = "/config_entity_revisions/{config_entity_revisions}/delete",
 *     "edit-form" = "/config_entity_revisions/{config_entity_revisions}/edit",
 *     "admin-form" = "/admin/structure/config_entity/manage/{config_entity_revisions_bundle}"
 *   }
 * )
 */
class ConfigEntityRevisions extends EditorialContentEntityBase implements ConfigEntityRevisionsEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['configuration'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Configuration'))
      ->setDescription(t('The serialised configuration for this revision.'))
      ->setReadOnly(TRUE)
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setRevisionable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the configuration entity was last edited.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['moderation_state'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Moderation state'))
      ->setDescription(t('The moderation state of this revision.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    return $fields;
  }

}
