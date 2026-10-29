<?php

namespace Drupal\webform_revisions;

use Drupal\Core\Utility\TableSort;

/**
 * Trait for webform revisions list builder.
 */
trait WebformRevisionsSubmissionListBuilderTrait {

  /**
   * {@inheritdoc}
   */
  protected function initialize() {
    parent::initialize();

    $storage = $this->getStorage();

    $ids = $this->getEntityIds();
    $submissions = $storage->loadMultiple($ids);

    $webforms = [];
    $columns = [];
    foreach ($submissions as $submission) {
      $webform = $submission->getWebform();
      $webformId = $webform->id();
      $webformRevisionId = $webform->getRevisionId();
      if (empty($webforms[$webformId])) {
        $webforms[$webformId] = [];
      }
      if (!empty($webforms[$webformId][$webformRevisionId])) {
        continue;
      }

      $webforms[$webformId][$webformRevisionId] = TRUE;
      $submissionColumns = $storage->getCustomColumns($submission->getWebform());
      $columns = array_merge($columns, $submissionColumns);
    }

    $this->columns = $columns;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {

    $query = $this->getQuery($this->keys, $this->state, $this->sourceEntityTypeId);

    // Yes, this is the only change from the function we're overriding. We need
    // to enforce the use of pager 0 because our build function above also gets
    // a query, resulting in this using 1 otherwise, which means it then doesn't
    // honour the pager argument in the request query.
    $query->pager($this->limit, 0);

    $header = $this->buildHeader();
    $order = TableSort::getOrder($header, $this->request);
    $direction = TableSort::getSort($header, $this->request);

    // If query is order(ed) by 'element__*' we need to build a custom table
    // sort using hook_query_TAG_alter().
    // @see webform_query_webform_submission_list_builder_alter()
    if ($order && strpos($order['sql'], 'element__') === 0) {
      $name = $order['sql'];
      $column = $this->columns[$name];
      $query->addTag('webform_submission_list_builder')
        ->addMetaData('webform_submission_element_name', $column['key'])
        ->addMetaData('webform_submission_element_property_name', $column['property_name'])
        ->addMetaData('webform_submission_element_direction', $direction);
      $result = $query->execute();
      // Must manually initialize the pager because the DISTINCT clause in the
      // query is breaking the row counting.
      // @see webform_query_alter()
      $this->pagerManager->createPager($this->total, $this->limit);
      return $result;
    }
    else {
      if ($order && $order['sql']) {
        $query->tableSort($header);
      }
      else {
        // If no order is specified, make sure the first column is sortable,
        // else default sorting to the sid.
        // @see \Drupal\Core\Entity\Query\QueryBase::tableSort
        // @see tablesort_get_order()
        $default = reset($header);
        if (isset($default['specified'])) {
          $query->tableSort($header);
        }
        else {
          $query->sort('sid', 'DESC');
        }
      }
      return $query->execute();
    }
  }

}
