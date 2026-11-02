<?php

namespace Drupal\views_revisions;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views_ui\ViewEditForm;

/**
 * Views revisions edit form.
 */
class ViewsRevisionsEditForm extends ViewEditForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    $entity = $this->getEntity();
    $revId = $entity->get('storage')->getRevisionId();
    $cacheId = ($revId) ? $entity->id() . '-' . $revId : $entity->id();

    $this->tempStore->delete($cacheId);
  }

  /**
   * Form submission handler for the 'cancel' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancel(array $form, FormStateInterface $form_state) {
    // Remove this view from cache so edits will be lost.
    parent::cancel($form, $form_state);

    $entity = $this->getEntity();
    $revId = $entity->get('storage')->getRevisionId();
    $cacheId = ($revId) ? $entity->id() . '-' . $revId : $entity->id();

    $this->tempStore->delete($cacheId);
  }

}
