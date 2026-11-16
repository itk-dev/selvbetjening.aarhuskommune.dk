<?php

namespace Drupal\views_revisions;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\EventSubscriber\AjaxResponseSubscriber;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
use Drupal\view\Plugin\ViewSourceEntityManagerInterface;
use Drupal\view\ViewEntityReferenceManagerInterface;
use Drupal\view\ViewInterface;
use Drupal\view\ViewRequest;
use Drupal\view\ViewRequestInterface;
use Drupal\view\ViewSubmissionInterface;
use Drupal\view_revisions\Controller\ViewRevisionsController;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles view requests.
 */
class ViewsRevisionsRequest extends ViewRequest implements ViewRequestInterface {

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The route admin context to determine whether a route is an admin one.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type repository.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * The view entity reference manager.
   *
   * @var \Drupal\view\ViewEntityReferenceManagerInterface
   */
  protected $viewEntityReferenceManager;

  /**
   * View source entity plugin manager.
   *
   * @var \Drupal\view\Plugin\ViewSourceEntityManagerInterface
   */
  protected $viewSourceEntityManager;

  /**
   * Track if the current page is a view admin route.
   *
   * @var bool
   */
  protected $isAdminRoute;

  /**
   * Constructs a ViewRevisionsRequest object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The route admin context service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   *   The entity type repository.
   * @param \Drupal\view\ViewEntityReferenceManagerInterface $view_entity_reference_manager
   *   The view entity reference manager.
   * @param \Drupal\view\Plugin\ViewSourceEntityManagerInterface $view_source_entity_manager
   *   The view source entity plugin manager.
   */
  public function __construct(RouteProviderInterface $route_provider, RequestStack $request_stack, AdminContext $admin_context, RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager, EntityTypeRepositoryInterface $entity_type_repository, ViewEntityReferenceManagerInterface $view_entity_reference_manager, ViewSourceEntityManagerInterface $view_source_entity_manager) {
    $this->routeProvider = $route_provider;
    $this->request = $request_stack->getCurrentRequest();
    $this->adminContext = $admin_context;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeRepository = $entity_type_repository;
    $this->viewEntityReferenceManager = $view_entity_reference_manager;
    $this->viewSourceEntityManager = $view_source_entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function isViewAdminRoute() {
    if (isset($this->isAdminRoute)) {
      return $this->isAdminRoute;
    }

    // Make sure the current route is an admin route.
    if (!$this->adminContext->isAdminRoute()) {
      $this->isAdminRoute = FALSE;
      return $this->isAdminRoute;
    }

    $route_name = $this->routeMatch->getRouteName();
    if (in_array($route_name, [
      'entity.view.canonical',
      'entity.view_submission.edit_form',
    ])) {
      $this->isAdminRoute = FALSE;
    }
    else {
      $this->isAdminRoute = (preg_match('/^(view\.|^entity\.([^.]+\.)?view)/', $route_name)) ? TRUE : FALSE;
    }
    return $this->isAdminRoute;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentSourceEntity($ignored_types = NULL) {
    // @todo Can we refactor this method away altogether and let all its callers
    // work directly with view source entity manager?
    return $this->viewSourceEntityManager->getSourceEntity(is_null($ignored_types) ? [] : $ignored_types);
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentView() {
    $source_entity = static::getCurrentSourceEntity('view');
    if ($source_entity && ($view = $this->viewEntityReferenceManager->getView($source_entity))) {
      return $view;
    }

    $view = $this->routeMatch->getParameter('view');
    if (is_string($view)) {
      $controller = ViewRevisionsController::create(\Drupal::getContainer());
      $revisionId = $source_entity->get('view_revision')->getValue();
      if ($revisionId) {
        $revisionId = $revisionId[0]['target_id'];
      }
      else {
        // Get the id by getting the default view's third party settings.
        $view = $this->entityTypeManager->getStorage('view')
          ->load($view);
        $content_entity_id = $view->getContentEntityId();
        // Get the first revision id.
        $revisionId = \Drupal::database()
          ->query('SELECT MIN(revision) FROM {config_entity_revisions_revision} c WHERE c.id = :id',
            [':id' => $content_entity_id])
          ->fetchField();
      }
      $view = $controller->loadConfigEntityRevision($revisionId);
    }
    return $view;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentViewSubmission() {
    $view_submission = $this->routeMatch->getParameter('view_submission');
    if (is_string($view_submission)) {
      $view_submission = $this->entityTypeManager->getStorage('view_submission')
        ->load($view_submission);
    }
    return $view_submission;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewEntities() {
    $view = $this->getCurrentView();
    $source_entity = $this->getCurrentSourceEntity('view');
    return [$view, $source_entity];
  }

  /**
   * {@inheritdoc}
   */
  public function getViewSubmissionEntities() {
    $view_submission = $this->routeMatch->getParameter('view_submission');
    if (is_string($view_submission)) {
      $view_submission = $this->entityTypeManager->getStorage('view_submission')
        ->load($view_submission);
    }
    $source_entity = $this->getCurrentSourceEntity('view_submission');
    return [$view_submission, $source_entity];
  }

  /**
   * {@inheritdoc}
   */
  public function isAjax() {
    return $this->request->get(AjaxResponseSubscriber::AJAX_REQUEST_PARAMETER) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl(EntityInterface $view_entity, EntityInterface $source_entity = NULL, $route_name, array $route_options = []) {
    $route_name = $this->getRouteName($view_entity, $source_entity, $route_name);
    $route_parameters = $this->getRouteParameters($view_entity, $source_entity);
    return Url::fromRoute($route_name, $route_parameters, $route_options);
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName(EntityInterface $view_entity, EntityInterface $source_entity = NULL, $route_name) {
    if (!$this->hasSourceEntityViewRoutes($source_entity)) {
      $source_entity = NULL;
    }

    return $this->getBaseRouteName($view_entity, $source_entity) . '.' . $route_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(EntityInterface $view_entity, EntityInterface $source_entity = NULL) {
    if (!$this->hasSourceEntityViewRoutes($source_entity)) {
      $source_entity = NULL;
    }

    if (static::isValidSourceEntity($view_entity, $source_entity)) {
      if ($view_entity instanceof ViewSubmissionInterface) {
        return [
          'view_submission' => $view_entity->id(),
          $source_entity->getEntityTypeId() => $source_entity->id(),
        ];
      }
      else {
        return [$source_entity->getEntityTypeId() => $source_entity->id()];
      }
    }
    elseif ($view_entity instanceof ViewSubmissionInterface) {
      return [
        'view_submission' => $view_entity->id(),
        'view' => $view_entity->getView()->id(),
      ];
    }
    else {
      return [$view_entity->getEntityTypeId() => $view_entity->id()];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseRouteName(EntityInterface $view_entity, EntityInterface $source_entity = NULL) {
    if ($view_entity instanceof ViewSubmissionInterface) {
      $view = $view_entity->getView();
    }
    elseif ($view_entity instanceof ViewInterface) {
      $view = $view_entity;
    }
    else {
      throw new \InvalidArgumentException('View entity');
    }

    if (static::isValidSourceEntity($view, $source_entity)) {
      return 'entity.' . $source_entity->getEntityTypeId();
    }
    else {
      return 'entity';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasSourceEntityViewRoutes(EntityInterface $source_entity = NULL) {
    if ($source_entity && $this->routeExists('entity.' . $source_entity->getEntityTypeId() . '.view_submission.canonical')) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isValidSourceEntity(EntityInterface $view_entity, EntityInterface $source_entity = NULL) {
    // Validate that source entity exists and can be linked to.
    if (!$source_entity || !$source_entity->hasLinkTemplate('canonical')) {
      return FALSE;
    }

    // Get the view.
    if ($view_entity instanceof ViewSubmissionInterface) {
      $view = $view_entity->getView();
    }
    elseif ($view_entity instanceof ViewInterface) {
      $view = $view_entity;
    }
    else {
      throw new \InvalidArgumentException('View entity');
    }

    // Validate that source entity's field target id is the correct view.
    $view_target = $this->viewEntityReferenceManager->getView($source_entity);
    if ($view_target && $view_target->id() == $view->id()) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Check if route exists.
   *
   * @param string $name
   *   Route name.
   *
   * @return bool
   *   TRUE if the route exists.
   *
   * @see http://drupal.stackexchange.com/questions/222591/how-do-i-verify-a-route-exists
   */
  protected function routeExists($name) {
    try {
      $this->routeProvider->getRouteByName($name);
      return TRUE;
    }
    catch (\Exception $exception) {
      return FALSE;
    }
  }

}
