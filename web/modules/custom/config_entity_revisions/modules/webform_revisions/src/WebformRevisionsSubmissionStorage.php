<?php

namespace Drupal\webform_revisions;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionStorage;
use Drupal\webform_revisions\Controller\WebformRevisionsController;

/**
 * Storage class overrides for webform revisions submissions.
 */
class WebformRevisionsSubmissionStorage extends WebformSubmissionStorage {

  /**
   * {@inheritdoc}
   */
  public function getColumns(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE) {
    $cachedColumns = &drupal_static(__FUNCTION__, []);
    $webformId = $webform ? $webform->id() : NULL;

    if ($webformId && !empty($cachedColumns[$webformId])) {
      return $cachedColumns[$webformId];
    }

    $view_any = ($webform && $webform->access('submission_view_any')) ? TRUE : FALSE;

    $columns = [];

    // Serial number.
    $columns['serial'] = [
      'title' => $this->t('#'),
    ];

    // Submission ID.
    $columns['sid'] = [
      'title' => $this->t('SID'),
    ];

    // Submission label.
    $columns['label'] = [
      'title' => $this->t('Submission title'),
      'sort' => FALSE,
    ];

    // UUID.
    $columns['uuid'] = [
      'title' => $this->t('UUID'),
    ];

    // Draft.
    $columns['in_draft'] = [
      'title' => $this->t('In draft'),
    ];

    if (empty($account)) {
      // Sticky (Starred/Unstarred).
      $columns['sticky'] = [
        'title' => $this->t('Starred'),
      ];

      // Locked.
      $columns['locked'] = [
        'title' => $this->t('Locked'),
      ];

      // Notes.
      $columns['notes'] = [
        'title' => $this->t('Notes'),
      ];
    }

    // Created.
    $columns['created'] = [
      'title' => $this->t('Created'),
    ];

    // Completed.
    $columns['completed'] = [
      'title' => $this->t('Completed'),
    ];

    // Changed.
    $columns['changed'] = [
      'title' => $this->t('Changed'),
    ];

    // Source entity.
    if ($view_any && empty($source_entity)) {
      $columns['entity'] = [
        'title' => $this->t('Submitted to'),
        'sort' => FALSE,
      ];
    }

    // Submitted by.
    if (empty($account)) {
      $columns['uid'] = [
        'title' => $this->t('User'),
      ];
    }

    // Submission language.
    if ($view_any && \Drupal::moduleHandler()->moduleExists('language')) {
      $columns['langcode'] = [
        'title' => $this->t('Language'),
      ];
    }

    // Remote address.
    $columns['remote_addr'] = [
      'title' => $this->t('IP address'),
    ];

    // Webform and source entity for entity.webform_submission.collection.
    // @see /admin/structure/webform/submissions/manage
    if (empty($webform) && empty($source_entity)) {
      $columns['webform_id'] = [
        'title' => $this->t('Webform'),
      ];
      $columns['entity'] = [
        'title' => $this->t('Submitted to'),
        'sort' => FALSE,
      ];
    }

    // Webform elements.
    if ($webform && $include_elements) {
      /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
      $element_manager = \Drupal::service('plugin.manager.webform.element');
      $content_entity_id = $webform->getContentEntityId();
      $revision_ids = $this->database->query(
        'SELECT revision FROM {config_entity_revisions_revision} WHERE id = :id',
        [':id' => $content_entity_id]
      )->fetchCol();
      if (!$revision_ids) {
        $cachedColumns[$webformId] = parent::getColumns($webform, $source_entity, $account, $include_elements);
        return $cachedColumns[$webformId];
      }

      foreach ($revision_ids as $revision_id) {
        $revisionController = WebformRevisionsController::create(\Drupal::getContainer());
        $webform = $revisionController->loadConfigEntityRevision($revision_id, $webform->id());
        $elements = $webform->getElementsInitializedFlattenedAndHasValue('view');
        foreach ($elements as $element) {
          /** @var \Drupal\webform\Plugin\WebformElementInterface $element_plugin */
          $element_plugin = $element_manager->createInstance($element['#type']);
          // Replace tokens which can be used in an element's #title.
          $element_plugin->replaceTokens($element, $webform);
          $columns += $element_plugin->getTableColumn($element);
        }
      }
    }

    // Operations.
    $columns['operations'] = [
      'title' => $this->t('Operations'),
      'sort' => FALSE,
    ];

    // Add name and format to all columns.
    foreach ($columns as $name => &$column) {
      $column['name'] = $name;
      $column['format'] = 'value';
    }

    $cachedColumns[$webformId] = $columns;
    return $columns;
  }

}
