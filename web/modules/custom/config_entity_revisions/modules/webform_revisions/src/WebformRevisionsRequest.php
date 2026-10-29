<?php

namespace Drupal\webform_revisions;

use Drupal\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\EventSubscriber\AjaxResponseSubscriber;
use Drupal\Core\Url;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformRequest;
use Drupal\webform\WebformRequestInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_revisions\Controller\WebformRevisionsController;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles webform requests.
 */
class WebformRevisionsRequest extends WebformRequest implements WebformRequestInterface {

  /**
   * Constructs a WebformRequest object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $routeProvider
   *   The route provider.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Routing\AdminContext $adminContext
   *   The route admin context service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entityTypeRepository
   *   The entity type repository.
   * @param \Drupal\webform\WebformEntityReferenceManagerInterface $webformEntityReferenceManager
   *   The webform entity reference manager.
   * @param \Drupal\webform\Plugin\WebformSourceEntityManagerInterface $webformSourceEntityManager
   *   The webform source entity plugin manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection service.
   * @param \Drupal\Core\DependencyInjection\ContainerInjectionInterface $container
   *   The Drupal service container.
   */
  public function __construct(protected $routeProvider, RequestStack $requestStack, protected $adminContext, protected $routeMatch, protected $entityTypeManager, protected $entityTypeRepository, protected $webformEntityReferenceManager, protected $webformSourceEntityManager, protected Connection $connection, protected ContainerInterface $container) {
    $this->request = $requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentSourceEntity($ignored_types = NULL) {
    // @todo Can we refactor this method away altogether and let all its callers
    // work directly with webform source entity manager?
    return $this->webformSourceEntityManager->getSourceEntity(is_null($ignored_types) ? [] : $ignored_types);
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentWebform() {
    $source_entity = static::getCurrentSourceEntity('webform');
    if ($source_entity && ($webform = $this->webformEntityReferenceManager->getWebform($source_entity))) {
      return $webform;
    }

    $webform = $this->routeMatch->getParameter('webform');
    if (is_string($webform)) {
      $controller = WebformRevisionsController::create($this->container);
      $revisionId = $source_entity->get('webform_revision')->getValue();
      if ($revisionId) {
        $revisionId = $revisionId[0]['target_id'];
      }
      else {
        // Get the id by getting the default webform's third party settings.
        $webform = $this->entityTypeManager->getStorage('webform')
          ->load($webform);
        $content_entity_id = $webform->getContentEntityId();
        // Get the first revision id.
        $revisionId = $this->connection
          ->query('SELECT MIN(revision) FROM {config_entity_revisions_revision} c WHERE c.id = :id',
            [':id' => $content_entity_id])
          ->fetchField();
      }
      $webform = $controller->loadConfigEntityRevision($revisionId);
    }
    return $webform;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentWebformSubmission() {
    $webform_submission = $this->routeMatch->getParameter('webform_submission');
    if (is_string($webform_submission)) {
      $webform_submission = $this->entityTypeManager->getStorage('webform_submission')
        ->load($webform_submission);
    }
    return $webform_submission;
  }

  /**
   * {@inheritdoc}
   */
  public function getWebformEntities() {
    $webform = $this->getCurrentWebform();
    $source_entity = $this->getCurrentSourceEntity('webform');
    return [$webform, $source_entity];
  }

  /**
   * {@inheritdoc}
   */
  public function getWebformSubmissionEntities() {
    $webform_submission = $this->routeMatch->getParameter('webform_submission');
    if (is_string($webform_submission)) {
      $webform_submission = $this->entityTypeManager->getStorage('webform_submission')
        ->load($webform_submission);
    }
    $source_entity = $this->getCurrentSourceEntity('webform_submission');
    return [$webform_submission, $source_entity];
  }

  /**
   * {@inheritdoc}
   */
  public function isAjax() {
    return (bool) ($this->request->query->get(AjaxResponseSubscriber::AJAX_REQUEST_PARAMETER) || $this->request->request->get(AjaxResponseSubscriber::AJAX_REQUEST_PARAMETER));
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl(EntityInterface $webform_entity, EntityInterface $source_entity = NULL, $route_name, array $route_options = []) {
    $route_name = $this->getRouteName($webform_entity, $source_entity, $route_name);
    $route_parameters = $this->getRouteParameters($webform_entity, $source_entity);
    return Url::fromRoute($route_name, $route_parameters, $route_options);
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName(EntityInterface $webform_entity, EntityInterface $source_entity = NULL, $route_name) {
    if (!$this->hasSourceEntityWebformRoutes($source_entity)) {
      $source_entity = NULL;
    }

    return $this->getBaseRouteName($webform_entity, $source_entity) . '.' . $route_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(EntityInterface $webform_entity, EntityInterface $source_entity = NULL) {
    if (!$this->hasSourceEntityWebformRoutes($source_entity)) {
      $source_entity = NULL;
    }

    if (static::isValidSourceEntity($webform_entity, $source_entity)) {
      if ($webform_entity instanceof WebformSubmissionInterface) {
        return [
          'webform_submission' => $webform_entity->id(),
          $source_entity->getEntityTypeId() => $source_entity->id(),
        ];
      }
      else {
        return [$source_entity->getEntityTypeId() => $source_entity->id()];
      }
    }
    elseif ($webform_entity instanceof WebformSubmissionInterface) {
      return [
        'webform_submission' => $webform_entity->id(),
        'webform' => $webform_entity->getWebform()->id(),
      ];
    }
    else {
      return [$webform_entity->getEntityTypeId() => $webform_entity->id()];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseRouteName(EntityInterface $webform_entity, EntityInterface $source_entity = NULL) {
    if ($webform_entity instanceof WebformSubmissionInterface) {
      $webform = $webform_entity->getWebform();
    }
    elseif ($webform_entity instanceof WebformInterface) {
      $webform = $webform_entity;
    }
    else {
      throw new \InvalidArgumentException('Webform entity');
    }

    if (static::isValidSourceEntity($webform, $source_entity)) {
      return 'entity.' . $source_entity->getEntityTypeId();
    }
    else {
      return 'entity';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasSourceEntityWebformRoutes(EntityInterface $source_entity = NULL) {
    if ($source_entity && $this->routeExists('entity.' . $source_entity->getEntityTypeId() . '.webform_submission.canonical')) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isValidSourceEntity(EntityInterface $webform_entity, EntityInterface $source_entity = NULL) {
    // Validate that source entity exists and can be linked to.
    if (!$source_entity || !$source_entity->hasLinkTemplate('canonical')) {
      return FALSE;
    }

    // Get the webform.
    if ($webform_entity instanceof WebformSubmissionInterface) {
      $webform = $webform_entity->getWebform();
    }
    elseif ($webform_entity instanceof WebformInterface) {
      $webform = $webform_entity;
    }
    else {
      throw new \InvalidArgumentException('Webform entity');
    }

    // Validate that source entity's field target id is the correct webform.
    $webform_target = $this->webformEntityReferenceManager->getWebform($source_entity);
    if ($webform_target && $webform_target->id() == $webform->id()) {
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
