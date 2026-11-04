<?php

namespace Drupal\webform_revisions\Form;

use Drupal\config_entity_revisions\ConfigEntityRevisionsOverviewFormBase;
use Drupal\config_entity_revisions\ConfigEntityRevisionsOverviewFormBaseInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for reverting a webform_revisions revision.
 *
 * @internal
 */
class WebformRevisionsOverviewForm extends ConfigEntityRevisionsOverviewFormBase implements ConfigEntityRevisionsOverviewFormBaseInterface {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ConfigEntityInterface $webform = NULL) {
    return parent::buildForm($form, $form_state, $webform);
  }

}
