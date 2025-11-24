<?php

namespace Drupal\os2forms_granular_permissions\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber to provide more granular permissions on certain routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    // Webform handler routes.
    $handlerRoutes = [
      'entity.webform.handlers',
      'entity.webform.handler',
      'entity.webform.handler.add_form',
      'entity.webform.handler.add_email',
      'entity.webform.handler.edit_form',
      'entity.webform.handler.duplicate_form',
      'entity.webform.handler.delete_form',
      'entity.webform.handler.enable',
      'entity.webform.handler.disable',
    ];

    foreach ($handlerRoutes as $routeName) {
      if ($route = $collection->get($routeName)) {
        $route->addRequirements([
          '_permission' => 'access webform handlers tab',
        ]);
      }
    }

    // Webform references routes.
    if ($route = $collection->get('entity.webform.references')) {
      $route->addRequirements([
        '_permission' => 'access webform references tab',
      ]);
    }
    if ($route = $collection->get('entity.webform.references.add_form')) {
      $route->addRequirements([
        '_permission' => 'access webform references tab',
      ]);
    }

    // Webform access route.
    if ($route = $collection->get('entity.webform.settings_access')) {
      $route->addRequirements([
        '_permission' => 'access webform access tab',
      ]);
    }

    // Webform export results routes.
    if ($route = $collection->get('entity.webform.results_export')) {
      $route->addRequirements([
        '_permission' => 'access webform results download tab',
      ]);
    }
    if ($route = $collection->get('entity.webform.results_export_file')) {
      $route->addRequirements([
        '_permission' => 'access webform results download tab',
      ]);
    }

    // Webform import results routes.
    if ($route = $collection->get('entity.node.webform.results_export')) {
      $route->addRequirements([
        '_permission' => 'access webform results download tab',
      ]);
    }
    if ($route = $collection->get('entity.node.webform.results_export_file')) {
      $route->addRequirements([
        '_permission' => 'access webform results download tab',
      ]);
    }
    if ($route = $collection->get('entity.webform_submission_export_import.results_import')) {
      $route->addRequirements([
        '_permission' => 'access webform results upload tab',
      ]);
    }
    if ($route = $collection->get('entity.node.webform_submission_export_import.results_import')) {
      $route->addRequirements([
        '_permission' => 'access webform results upload tab',
      ]);
    }

    // Webform clear results routes.
    if ($route = $collection->get('entity.webform.results_clear')) {
      $route->addRequirements([
        '_permission' => 'access webform results clear tab',
      ]);
    }
    if ($route = $collection->get('entity.node.webform.results_clear')) {
      $route->addRequirements([
        '_permission' => 'access webform results clear tab',
      ]);
    }

    // Webform submission routes.
    if ($route = $collection->get('entity.webform_submission.notes_form')) {
      $route->addRequirements([
        '_permission' => 'access webform submission_notes',
      ]);
    }
    if ($route = $collection->get('entity.node.webform_submission.notes_form')) {
      $route->addRequirements([
        '_permission' => 'access webform submission_notes',
      ]);
    }

    // Webform submission edit routes:
    $webformSubmissionEditRoutes = [
      'entity.webform.user.submission.edit',
      'entity.webform_submission.edit_form',
      'entity.webform_submission.edit_form.all',
      'entity.node.webform.user.submission.edit',
      'entity.node.webform_submission.edit_form',
      'entity.node.webform_submission.edit_form.all',
    ];

    foreach ($webformSubmissionEditRoutes as $webformSubmissionEditRoute) {
      if ($route = $collection->get($webformSubmissionEditRoute)) {
        $route->addRequirements([
          '_permission' => 'edit any webform submission',
        ]);
      }
    }
  }

}
