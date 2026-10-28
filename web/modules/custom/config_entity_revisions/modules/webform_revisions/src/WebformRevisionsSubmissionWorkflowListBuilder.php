<?php

namespace Drupal\webform_revisions;

use Drupal\webform_workflows_element\WebformSubmissionWorkflowListBuilder;

/**
 * Webform revisions submiision workflow list builder class.
 */
class WebformRevisionsSubmissionWorkflowListBuilder extends WebformSubmissionWorkflowListBuilder {
  use WebformRevisionsSubmissionListBuilderTrait;

}
