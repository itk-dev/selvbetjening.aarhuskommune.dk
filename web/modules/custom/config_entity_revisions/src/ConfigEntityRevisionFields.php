<?php

namespace Drupal\config_entity_revisions;

/**
 * Class defining revision form fields for the entity type.
 */
class ConfigEntityRevisionFields implements ConfigEntityRevisionFieldsInterface {

  /**
   * Should new revisions created by default?
   *
   * @return bool
   *   No new revision by default.
   */
  public static function getNewRevisionDefault() {
    return FALSE;
  }

  /**
   * Adds the revision form fields to the form.
   *
   * @param array &$form
   *   The form to which the fields should be added.
   */
  public static function addRevisionFormFields(&$form) {
    // Add a log field if the "Create new revision" option is checked, or if the
    // current user has the ability to check that option.
    $new_revision_default = self::getNewRevisionDefault();

    $form['revision'] = [
      '#type' => 'checkbox',
      '#title' => t('Create new revision'),
      '#default_value' => $new_revision_default,
      '#group' => 'revision_information',
    ];
  }

}
