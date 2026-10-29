<?php

namespace Drupal\config_entity_revisions\Plugin\WorkflowType;

use Drupal\content_moderation\Plugin\WorkflowType\ContentModeration;

/**
 * Attaches workflows to config entity revision content bundles.
 *
 * @WorkflowType(
 *   id = "config_entity_revisions_moderation",
 *   label = @Translation("Config Entity Revision moderation"),
 *   required_states = {
 *     "draft",
 *     "published",
 *   },
 *   forms = {
 *     "configure" = "\Drupal\content_moderation\Form\ContentModerationConfigureForm",
 *     "state" = "\Drupal\content_moderation\Form\ContentModerationStateForm"
 *   },
 * )
 */
class ConfigEntityRevisionsModeration extends ContentModeration {}
