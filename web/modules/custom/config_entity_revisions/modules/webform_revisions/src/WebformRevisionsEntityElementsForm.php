<?php

namespace Drupal\webform_revisions;

use Drupal\config_entity_revisions\RevisionableEntityFormTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformEntityElementsForm;

/**
 * Webform revisions entity elements form.
 */
class WebformRevisionsEntityElementsForm extends WebformEntityElementsForm {

  use RevisionableEntityFormTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = $this->revisionableEntityFormEarly($form, $form_state);
    $form = parent::buildForm($form, $form_state);
    return $this->revisionableEntityFormLate($form, $form_state);
  }

}
