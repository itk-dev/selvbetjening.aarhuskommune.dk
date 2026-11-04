<?php

namespace Drupal\views_revisions\Form;

use Drupal\config_entity_revisions\ConfigEntityRevisionsOverviewFormBase;
use Drupal\config_entity_revisions\ConfigEntityRevisionsOverviewFormBaseInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for reverting a view_revisions revision.
 *
 * @internal
 */
class ViewsRevisionsOverviewForm extends ConfigEntityRevisionsOverviewFormBase implements ConfigEntityRevisionsOverviewFormBaseInterface {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ConfigEntityInterface $view = NULL) {
    return parent::buildForm($form, $form_state, $view);
  }

}
