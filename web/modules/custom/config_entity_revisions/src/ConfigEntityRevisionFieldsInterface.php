<?php

namespace Drupal\config_entity_revisions;

/**
 * Class defining revision form fields for the entity type.
 */
interface ConfigEntityRevisionFieldsInterface {

  /**
   * Should new revisions created by default?
   *
   * @return bool
   *   No new revision by default.
   */
  public static function getNewRevisionDefault();

  /**
   * Adds the revision form fields to the form.
   *
   * @param array &$form
   *   The form to which the fields should be added.
   */
  public static function addRevisionFormFields(&$form);

}
