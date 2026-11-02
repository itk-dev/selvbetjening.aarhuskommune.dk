<?php

namespace Drupal\views_revisions;

use Drupal\config_entity_revisions\ConfigEntityRevisionsConfigTrait;
use Drupal\views_ui\ViewUI;

/**
 * Views revisions UI class.
 */
class ViewsRevisionsUI extends ViewUI {

  use ViewsRevisionsConfigTrait, ConfigEntityRevisionsConfigTrait;

  /**
   * Sets a cached view object in the shared tempstore.
   */
  public function cacheSet() {
    if ($this->isLocked()) {
      \Drupal::messenger()->addError('Changes cannot be made to a locked view.');
      return;
    }

    // Let any future object know that this view has changed.
    $this->changed = TRUE;

    $executable = $this->getExecutable();
    if (isset($executable->current_display)) {
      // Add the knowledge of the changed display, too.
      $this->changed_display[$executable->current_display] = TRUE;
      $executable->current_display = NULL;
    }

    // Unset handlers. We don't want to write these into the cache.
    $executable->display_handler = NULL;
    $executable->default_display = NULL;
    $executable->query = NULL;
    $executable->displayHandlers = NULL;
    $revId = $this->get('storage')->getRevisionId();
    $cacheId = ($revId) ? $this->id() . '-' . $revId : $this->id();
    $this->storage->entityTypeManager = NULL;
    $this->entityTypeManager = NULL;
    \Drupal::service('tempstore.shared')->get('views')->set($cacheId, $this);
  }

}
