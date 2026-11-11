<?php

namespace Drupal\views_revisions\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber for views revisions.
 *
 * @package Drupal\views_revisions\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $latest_revision_routes = [
      'entity.views.source_form',
      'entity.views.edit_form',
      'entity.views_ui.element.edit_form',
    ];

    foreach ($latest_revision_routes as $route_name) {
      if ($route = $collection->get($route_name)) {
        $current = $route->getOption('parameters') ?: [];
        $current['views']['load_latest_revision'] = TRUE;
        $route->setOption('parameters', $current);
      }
    }
  }

}
