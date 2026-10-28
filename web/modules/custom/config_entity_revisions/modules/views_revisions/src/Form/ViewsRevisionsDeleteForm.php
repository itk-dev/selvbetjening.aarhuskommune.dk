<?php

namespace Drupal\views_revisions\Form;

use Drupal\config_entity_revisions\ConfigEntityRevisionsDeleteFormBase;
use Drupal\views_revisions\Controller\ViewsRevisionsController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a ViewRevisions revision.
 *
 * @internal
 */
class ViewsRevisionsDeleteForm extends ConfigEntityRevisionsDeleteFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity_type.manager');
    return new static(
      $entity_manager->getStorage('config_entity_revisions'),
      $container->get('database'),
      $container->get('date.formatter'),
      ViewsRevisionsController::create($container)
    );
  }

}
