<?php

namespace Drupal\webform_revisions;

use Drupal\config_entity_revisions\ConfigEntityRevisionsStorageTrait;

/**
 * Configuration trait for webform revisions.
 */
trait WebformRevisionsConfigTrait {
  use ConfigEntityRevisionsStorageTrait;

  /**
   * Constants for webform revisions.
   *
   * @var array
   */
  private $constants = [
    'module_name' => 'webform_revisions',
    'config_entity_name' => 'webform',
    'revisions_entity_name' => 'WebformRevisions',
    'setting_name' => 'webform_revisions_id',
    'title' => 'Webform',
    'has_own_content' => TRUE,
    'content_entity_type' => 'webform_submissions',
    'content_entity_table' => 'webform_submissions',
    'content_parameter_name' => 'webform_submission',
    'content_parent_reference_field' => 'webform',
    'admin_permission' => 'administer webform',
    'has_canonical_url' => TRUE,
  ];

  /**
   * Get the number of submissions related to a revision.
   *
   * @return int
   *   The number of content entities using a particular revision.
   */
  public function contentEntityCount($rid) {
    return \Drupal::database()
      ->query("SELECT COUNT(sid) FROM {webform_submission} WHERE webform_revision = :rid",
        [':rid' => $rid])->fetchField();
  }

  /**
   * Delete submissions related to a revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $revision
   *   The revision for which related content entities are being deleted.
   */
  public function deleteRelatedContentEntities($revision) {
    // Direct submissions attached directly to this revision.
    $sids = \Drupal::database()
      ->query("SELECT sid FROM {webform_submission} WHERE webform_revision = :rid",
        [':rid' => $this->revision->value])->fetchCol();
    $storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
    $submissions = $storage->loadMultiple($sids);
    $storage->delete($submissions);
  }

  /**
   * May this revision be deleted?
   *
   * @return int
   *   Whether this revision can be deleted.
   */
  public function mayDeleteRevision() {
    if ($this->hasOwnContent()) {
      $count = $this->contentEntityCount($this->getRevisionId());
      if ($count && !\Drupal::configFactory()->get('webform_revisions')
        ->get('allow_delete_with_submissions')) {
        return FALSE;
      }
    }

    $mayDelete = TRUE;
    \Drupal::moduleHandler()->invokeAll('config_entity_revision_may_delete', [$this, &$mayDelete]);
    return $mayDelete;
  }

  /**
   * Get the entity that actually has revisions.
   */
  public function revisionedEntity() {
    return $this;
  }

}
