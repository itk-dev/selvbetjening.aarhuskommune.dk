<?php

namespace Drupal\webform_revisions\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subsscriber for webform revisions support.
 *
 * @package Drupal\webform_revisions\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $latest_revision_routes = [
      'entity.webform.source_form',
      'entity.webform.edit_form',
      'entity.webform_ui.element',
      'entity.webform_ui.change_element',
      'entity.webform_ui.element.add_form',
      'entity.webform_ui.element.add_page',
      'entity.webform_ui.element.add_layout',
      'entity.webform_ui.element.edit_form',
      'entity.webform_ui.element.delete_form',
      'entity.webform_ui.element.duplicate_form',
      'entity.webform_ui.element.test_form',
    ];

    foreach ($latest_revision_routes as $route_name) {
      if ($route = $collection->get($route_name)) {
        $current = $route->getOption('parameters') ?: [];
        $current['webform']['load_latest_revision'] = TRUE;
        $route->setOption('parameters', $current);
      }
    }
  }

}
