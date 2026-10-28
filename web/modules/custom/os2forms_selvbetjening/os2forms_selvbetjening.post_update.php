<?php

/**
 * @file
 * Post update functions for os2forms_selvbetjening.
 */

/**
 * Remove webform revisions.
 *
 * Deletes all config entity revisions and uninstalls webform_revisions and
 * config_entity_revisions. The current configuration of each webform is kept.
 * The module code is removed in a later release.
 */
function os2forms_selvbetjening_post_update_remove_webform_revisions(array &$sandbox): void {
  $moduleHandler = \Drupal::moduleHandler();
  if (!$moduleHandler->moduleExists('config_entity_revisions')) {
    $sandbox['#finished'] = 1;
    return;
  }

  $storage = \Drupal::entityTypeManager()->getStorage('config_entity_revisions');

  if (!isset($sandbox['ids'])) {
    _os2forms_selvbetjening_remove_webform_revisions_settings();
    $sandbox['ids'] = array_values($storage->getQuery()->accessCheck(FALSE)->execute());
    $sandbox['total'] = count($sandbox['ids']);
  }

  // Each entity carries every revision of one webform, so keep chunks small.
  $ids = array_splice($sandbox['ids'], 0, 20);
  if ($ids) {
    $storage->delete($storage->loadMultiple($ids));
  }

  $sandbox['#finished'] = $sandbox['total'] > 0
    ? 1 - count($sandbox['ids']) / $sandbox['total']
    : 1;
  if ($sandbox['#finished'] < 1) {
    return;
  }

  // Clear submission references to revisions.
  $database = \Drupal::database();
  if ($database->schema()->fieldExists('webform_submission', 'webform_revision')) {
    $database->update('webform_submission')
      ->fields(['webform_revision' => NULL])
      ->execute();
  }

  // Finally, uninstall webform_revisions and config_entity_revisions.
  $modules = array_filter(
    ['webform_revisions', 'config_entity_revisions'],
    $moduleHandler->moduleExists(...)
  );
  \Drupal::service('module_installer')->uninstall($modules);
}

/**
 * Removes webform_revisions' settings and dependency from webform config.
 */
function _os2forms_selvbetjening_remove_webform_revisions_settings(): void {
  $configFactory = \Drupal::configFactory();
  $names = array_merge(
    $configFactory->listAll('webform.webform.'),
    $configFactory->listAll('webform.webform_options.')
  );

  foreach ($names as $name) {
    $config = $configFactory->getEditable($name);
    $modules = $config->get('dependencies.module') ?? [];
    if ($config->get('third_party_settings.webform_revisions') === NULL
      && !in_array('webform_revisions', $modules, TRUE)) {
      continue;
    }
    $config->clear('third_party_settings.webform_revisions');
    $modules = array_values(array_diff($modules, ['webform_revisions']));
    if ($modules) {
      $config->set('dependencies.module', $modules);
    }
    else {
      $config->clear('dependencies.module');
    }
    $config->save();
  }

  // The entity caches still hold the pointers.
  $entityTypeManager = \Drupal::entityTypeManager();
  $entityTypeManager->getStorage('webform')->resetCache();
  $entityTypeManager->getStorage('webform_options')->resetCache();
}
