<?php

namespace Drupal\webform_revisions\Form;

use Drupal\config_entity_revisions\ConfigEntityRevisionsDeleteFormBase;
use Drupal\webform_revisions\Controller\WebformRevisionsController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a WebformRevisions revision.
 *
 * @internal
 */
class WebformRevisionsDeleteForm extends ConfigEntityRevisionsDeleteFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity_type.manager');
    return new static(
      $entity_manager->getStorage('config_entity_revisions'),
      $container->get('database'),
      $container->get('date.formatter'),
      WebformRevisionsController::create($container),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {

    $output = 'This revision';
    $additional = [];

    if ($this->configEntity->hasOwnContent()) {
      $count = $this->configEntity->contentEntityCount($this->revision->revision->value);
      if ($count) {
        $additional['submissions']['count_desc'] = [$count, '1 submission', '@count submissions'];
      }
    }

    // Allow other modules (eg webform_nested) to alter the description.
    $arguments = [$this->configEntity, &$additional];
    $this->moduleHandler->invokeAll('config_entity_revision_delete_warning', $arguments);
    $index = 0;
    foreach ($additional as $effect) {
      $index++;
      switch ($index) {
        case ($index == count($additional)):
          $output .= ' and ';
          break;

        default:
          $output .= ', ';
      }
      $output .= (string) \Drupal::service('string_translation')->formatPlural(...$effect['count_desc']);
      $bracket = FALSE;

      if (!empty($effect['list'])) {
        $output .= ' (' . implode(', ', $effect['list']);
        $bracket = TRUE;
      }

      if (!empty($effect['default'])) {
        $output .= ($bracket ? " - " : "(") . "{$effect["default"]} is the default revision) ";
      }
      else {
        $output .= ($bracket ? ')' : '');
      }

    }
    $output .= ' will be deleted.';
    return $output;
  }

}
