<?php

namespace Drupal\views_revisions\Controller;

use Drupal\config_entity_revisions\ConfigEntityRevisionsControllerBase;
use Drupal\config_entity_revisions\ConfigEntityRevisionsControllerInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\views_revisions\ViewsRevisionsConfigTrait;

/**
 * Controller to make library functions available to various consumers.
 */
class ViewsRevisionsController extends ConfigEntityRevisionsControllerBase implements ConfigEntityRevisionsControllerInterface {

  use ViewsRevisionsConfigTrait;

  /**
   * Generates a title for the revision.
   *
   * This function is needed because the $Views parameter needs to match
   * the route but the parent's parameter is named $configEntity.
   *
   * @inheritdoc
   */
  public function revisionShowTitle(ConfigEntityInterface $view) {
    return '"' . $view->get('title') . '" Views, revision ' . $view->getRevisionId();
  }

}
