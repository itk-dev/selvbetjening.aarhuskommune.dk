<?php

namespace Drupal\os2forms_selvbetjening\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;


use Drupal\os2forms_selvbetjening\Entity\WebformSubmission;
use Drupal\webform\Entity\WebformSubmission as BaseWebformSubmission;

/**
 * Entity type hooks.
 */
class EntityTypeHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) {
    // Lifted from webform_revisions.module.
    if (isset($entity_types['webform_submission'])) {
      $type = $entity_types['webform_submission'];
      if ($type->getClass() === BaseWebformSubmission::class) {
        $type->setClass(WebformSubmission::class);
      }
    }
  }

}
