<?php

namespace Drupal\config_entity_revisions;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\AccessAwareRouter;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\diff\DiffEntityComparison;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Serializer;

/**
 * ConfigEntityRevisionsController interface.
 */
interface ConfigEntityRevisionsControllerInterface extends ContainerInjectionInterface {

  /**
   * Constructs a ConfigEntityRevisionsController object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container interface object.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The configuration service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Symfony\Component\Serializer\Serializer $serialiser
   *   The serialiser service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\content_moderation\ModerationInformation $contentModerationInformation
   *   The content moderation information service.
   * @param \Drupal\diff\DiffEntityComparison $entityComparison
   *   The diff entity comparison service.
   */
  public function __construct(
    ContainerInterface $container,
    DateFormatterInterface $dateFormatter,
    RendererInterface $renderer,
    ImmutableConfig $config,
    EntityTypeManagerInterface $entityTypeManager,
    AccountProxyInterface $currentUser,
    Serializer $serialiser,
    Connection $connection,
    $moduleHandler = NULL,
    $contentModerationInformation = NULL,
    DiffEntityComparison $entityComparison = NULL,
  );

  /**
   * Set extra services that come after the optional ones.
   *
   * @param \Drupal\Core\Routing\AccessAwareRouter $router
   *   The router service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function setExtraServices(
    AccessAwareRouter $router,
    RequestStack $requestStack,
  );

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container);

  /**
   * Create revision when a new config entity version is saved.
   *
   * @param \Drupal\config_entity_revisions\ConfigEntityRevisionsInterface $config_entity
   *   The configuration entity.
   */
  public function createUpdateRevision(ConfigEntityRevisionsInterface $config_entity);

  /**
   * Delete revisions when a config entity is deleted.
   *
   * @param \Drupal\config_entity_revisions\ConfigEntityRevisionsInterface $config_entity
   *   The configuration entity being deleted.
   */
  public function deleteRevisions(ConfigEntityRevisionsInterface $config_entity);

  /**
   * Default implementation providing a title for a rendered revision.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $config_entity
   *   The configuration entity being displayed.
   *
   * @return string
   *   The resulting title.
   */
  public function revisionShowTitle(ConfigEntityInterface $config_entity);

}
