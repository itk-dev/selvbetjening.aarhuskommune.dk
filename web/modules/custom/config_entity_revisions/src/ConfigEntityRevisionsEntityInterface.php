<?php

namespace Drupal\config_entity_revisions;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionLogInterface;

/**
 * Provides an interface defining a config entity revisions entity.
 */
interface ConfigEntityRevisionsEntityInterface extends ContentEntityInterface, EntityChangedInterface, RevisionLogInterface, EntityPublishedInterface {}
