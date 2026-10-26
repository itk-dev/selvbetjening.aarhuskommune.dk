<?php

namespace Drupal\config_entity_revisions\Entity;

use Drupal\config_entity_revisions\ConfigEntityRevisionsTypeInterface;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Config Entity Revisions configuration entity.
 *
 * @ConfigEntityType(
 *   id = "config_entity_revisions_type",
 *   label = @Translation("Config Entity Revisions type"),
 *   handlers = {
 *     "access" = "Drupal\config_entity_revisions\ConfigEntityRevisionsTypeAccessControlHandler",
 *     "list_builder" = "Drupal\config_entity_revisions\ConfigEntityRevisionsTypeListBuilder",
 *   },
 *   admin_permission = "administer config entity revision types",
 *   config_prefix = "config_entity_revisions_type",
 *   bundle_of = "config_entity_revisions",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "collection" = "/admin/structure/config_entity_revisions",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "help",
 *     "preview_mode",
 *     "display_submitted",
 *   }
 * )
 */
class ConfigEntityRevisionsType extends ConfigEntityBundleBase implements ConfigEntityRevisionsTypeInterface {

  /**
   * The machine name of this config_entity_revisions type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the config_entity_revisions type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this config_entity_revisions type.
   *
   * @var string
   */
  protected $description;

  /**
   * Help information shown to the user when creating content of this type.
   *
   * @var string
   */
  protected $help;

  /**
   * Default value of the 'Create new revision' checkbox.
   *
   * @var bool
   */
  protected $new_revision = TRUE;

  /**
   * The preview mode.
   *
   * @var int
   */
  protected $preview_mode = DRUPAL_OPTIONAL;

  /**
   * Display setting for author and date Submitted by post information.
   *
   * @var bool
   */
  protected $display_submitted = TRUE;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('config_entity_revisions.type.locked');
    return $locked[$this->id()] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isNewRevision() {
    return $this->new_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function setNewRevision($new_revision) {
    $this->new_revision = $new_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function displaySubmitted() {
    return $this->display_submitted;
  }

  /**
   * {@inheritdoc}
   */
  public function setDisplaySubmitted($display_submitted) {
    $this->display_submitted = $display_submitted;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewMode() {
    return $this->preview_mode;
  }

  /**
   * {@inheritdoc}
   */
  public function setPreviewMode($preview_mode) {
    $this->preview_mode = $preview_mode;
  }

  /**
   * {@inheritdoc}
   */
  public function getHelp() {
    return $this->help;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    return $this->isNewRevision();
  }

}
