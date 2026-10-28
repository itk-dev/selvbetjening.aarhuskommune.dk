<?php

namespace Drupal\config_entity_revisions;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\AccessAwareRouter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Serializer;

/**
 * Controller to make library functions available to various consumers.
 */
abstract class ConfigEntityRevisionsControllerBase extends ControllerBase implements ConfigEntityRevisionsControllerInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The router service.
   *
   * @var \Drupal\Core\Routing\AccessAwareRouter
   */
  protected $router;

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
    protected ContainerInterface $container,
    protected DateFormatterInterface $dateFormatter,
    protected RendererInterface $renderer,
    protected ImmutableConfig $config,
    protected $entityTypeManager,
    protected $currentUser,
    protected Serializer $serialiser,
    protected Connection $connection,
    protected $moduleHandler = NULL,
    protected $contentModerationInformation = NULL,
    protected $entityComparison = NULL,
  ) {}

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
  ) {
    $this->router = $router;
    $this->currentRequest = $requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_comparison = $container->has('diff.entity_comparison') ?
      $container->get('diff.entity_comparison') : NULL;

    $contentModerationInformation = $container->has('content_moderation.moderation_information') ?
      $container->get('content_moderation.moderation_information') : NULL;

    $instance = new static(
      $container,
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('config.factory')->get('diff.settings'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('serializer'),
      $container->get('database'),
      $container->get('module_handler'),
      $contentModerationInformation,
      $entity_comparison,
    );

    $instance->setExtraServices(
      $container->get('router'),
      $container->get('request_stack'),
    );

    return $instance;
  }

  /**
   * Create revision when a new config entity version is saved.
   *
   * @param ConfigEntityRevisionsInterface $configEntity
   *   The configuration entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createUpdateRevision(ConfigEntityRevisionsInterface $configEntity) {
    if ($configEntity->get('content_entity_save')) {
      return;
    }

    /** @var \Drupal\config_entity_revisions\ConfigEntityRevisionsEntityInterface $contentEntity */
    $contentEntity = NULL;
    // Default is unpublished.
    $revisionPublished = FALSE;
    $revisionWasPublished = FALSE;

    $alreadyDoingRevisionUpdate = !empty($configEntity->revisionUpdate) ? $configEntity->revisionUpdate : FALSE;
    unset($configEntity->revisionUpdate);

    $contentID = $configEntity->getContentEntityID();
    if ($contentID && !$configEntity->contentEntityStorage()->load($contentID)) {
      $contentID = NULL;
    }
    $currentRevision = $configEntity->getRevisionId();
    if (!$configEntity->contentEntityStorage()
      ->loadRevision($currentRevision)) {
      $currentRevision = NULL;
    }

    $newRevision = $contentEntityNeedsSave = !$alreadyDoingRevisionUpdate &&
      (!$contentID || $configEntity->get('revision'));

    if (!$contentID) {
      $source = $configEntity->original ?? $configEntity;

      /** @var \Drupal\Core\Entity\ContentEntityInterface $contentEntity */
      $contentEntity = $configEntity->contentEntityStorage()->create([
        'configuration' => $this->serialiser->serialize($source, 'json'),
        'type' => $configEntity->getEntityTypeId() . "_revisions",
        'revision_log_message' => $this->t('Initial revision'),
      ]);

      $revisionPublished = $source->status();
    }
    else {
      if (!empty($currentRevision)) {
        $contentEntity = $configEntity->contentEntityStorage()
          ->loadRevision($currentRevision);
      }

      if (!$contentEntity) {
        $contentEntity = $configEntity->contentEntityStorage()
          ->load($contentID);
      }

      if ($newRevision) {
        $contentEntity = $configEntity->contentEntityStorage()
          ->createRevision($contentEntity);
      }

      $serialised = $this->serialiser->serialize($configEntity, 'json');
      if ($serialised !== $contentEntity->get('configuration')) {
        $contentEntity->set('configuration', $serialised);
        $contentEntityNeedsSave = TRUE;
      }
      $revisionWasPublished = $contentEntity->isPublished();

      $message = $configEntity->get('revision_log_message');
      $new_message = $message ? $message[0]['value'] : '';

      if (!$alreadyDoingRevisionUpdate && !empty($new_message)) {
        if ($contentEntity->getRevisionLogMessage() != $new_message) {
          $contentEntity->setRevisionLogMessage($new_message);
          $contentEntityNeedsSave = TRUE;
        }
      }
    }

    if ($this->currentUser->id() != $contentEntity->getRevisionUserId()) {
      $contentEntity->setRevisionUserId($this->currentUser->id());
      $contentEntityNeedsSave = TRUE;
    }

    if ($this->moduleHandler->moduleExists('content_moderation')) {
      /** @var \Drupal\content_moderation\ModerationInformationInterface $moderation_info */
      $moderation_info = $this->contentModerationInformation;
      $moderation_enabled = $moderation_info->shouldModerateEntitiesOfBundle($contentEntity->getEntityType(), $contentEntity->bundle());
    }
    else {
      $moderation_enabled = FALSE;
    }
    if ($moderation_enabled) {
      $contentEntityModerationState = $revisionPublished ? 'published' : 'draft';

      if (!is_null($configEntity->get('moderation_state'))) {
        $contentEntityModerationState = $configEntity->get('moderation_state')[0]['value'];
      }

      if ($contentEntity->moderation_state->value != $contentEntityModerationState) {
        $contentEntity->moderation_state->value = $contentEntityModerationState;
        $contentEntityNeedsSave = TRUE;
      }
    }

    $wasDefaultRevision = $contentEntity->isDefaultRevision();

    $newPublishState =
      ((is_string($revisionPublished) && $revisionPublished == 'published') ||
        (!is_string($revisionPublished) && !$revisionPublished));

    if ($newPublishState != $contentEntity->isPublished()) {
      if ($revisionPublished) {
        $contentEntity->setPublished();
      }
      else {
        $contentEntity->setUnpublished();
      }

      $contentEntity->isDefaultRevision($revisionPublished);
      $contentEntityNeedsSave = TRUE;
    }

    if ($contentEntityNeedsSave) {
      $contentEntity->setRevisionCreationTime($this->container->get('datetime.time')
        ->getRequestTime());
      $contentEntity->save();
    }

    // Load a fresh, fully saved version.
    if ($contentEntity->id() !== $configEntity->getContentEntityId() ||
      $contentEntity->getRevisionId() !== $configEntity->getThirdPartySetting('webform_revisions', 'revisionId')) {
      $configEntity = $configEntity->configEntityStorage()
        ->load($configEntity->id());
      $configEntity->setContentEntityId($contentEntity->id());
      $configEntity->setThirdPartySetting('webform_revisions', 'revisionId', $contentEntity->getRevisionId());
      $configEntity->revisionUpdate = TRUE;
      $configEntity->save();
    }

    if (!$newPublishState && $revisionWasPublished && $wasDefaultRevision) {
      // Modify another revision to be published and default if possible.
      $this->resetDefaultRevision($contentEntity);
    }

    if ($newRevision) {
      $arguments = [$configEntity, $contentEntity];
      $this->moduleHandler()
        ->invokeAll($configEntity->configEntityName() . '_revision_insert', $arguments);
      $this->moduleHandler()->invokeAll('revision_insert', $arguments);
    }

    return $contentEntity;
  }

  /**
   * Make default the most recently published or most recent revision.
   *
   * This is needed because content_moderation has a concept of a default
   * revision, which this module doesn't really care about, but which will
   * cause problems if we attempt to delete a revision that's marked as the
   * default.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $content_entity
   *   The content (revisions) entity.
   */
  public function resetDefaultRevision(ContentEntityInterface $content_entity) {
    $content_entity_id = $content_entity->id();

    $revisions = $this->connection
      ->select("config_entity_revisions_revision", 'c')
      ->fields('c', ['revision', 'revision_default', 'published'])
      ->condition('id', $content_entity_id)
      ->orderBy('revision', 'DESC')
      ->execute()
      ->fetchAllAssoc('revision');

    $first_published = NULL;
    $first_revision = NULL;
    $remove_default = [];

    foreach ($revisions as $revision) {
      if (!$first_revision) {
        $first_revision = $revision;
      }

      if ($revision->published && !$first_published) {
        $first_published = $revision;
      }

      if ($revision->revision_default) {
        $remove_default[$revision->revision] = 1;
      }
    }

    $default_revision = $first_published ?: $first_revision;

    if ($default_revision) {
      unset($remove_default[$default_revision->revision]);
    }

    if (!empty($remove_default)) {
      $this->connection->update("config_entity_revisions_revision")
        ->condition('revision', array_keys($remove_default), 'IN')
        ->fields(['revision_default' => 0])
        ->execute();
    }

    if ($default_revision) {
      if (!$default_revision->revision_default) {
        $this->connection->update("config_entity_revisions_revision")
          ->condition('revision', $default_revision->revision)
          ->fields(['revision_default' => 1])
          ->execute();
      }

      $this->connection->update("config_entity_revisions")
        ->condition('id', $content_entity_id)
        ->fields(['revision' => $default_revision->revision])
        ->execute();
    }
  }

  /**
   * Get a list of revision IDs for a content entity.
   */
  public function getRevisionIds($content_entity_id) {
    $revisions = $this->connection->select("config_entity_revisions_revision", 'c')
      ->fields('c', ['revision'])
      ->condition('id', $content_entity_id)
      ->orderBy('id')
      ->execute()
      ->fetchCol();
    return $revisions;
  }

  /**
   * Delete a single revision.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $revision
   *   The revision to be deleted.
   */
  public function deleteRevision($revision) {
    $was_default = $revision->isDefaultRevision();

    // Let other modules delete other content that might be related.
    $this->moduleHandler->invokeAll('config_entity_revision_delete_related_content', [$revision]);

    if ($this->configEntity->hasOwnContent()) {
      $this->configEntity->deleteRelatedContentEntities($revision);
    }

    $revisions = $this->getRevisionIds($revision->id());

    if ($was_default) {
      // Change the default to the next newer (if we're deleting the default,
      // there must be no published revisions so it doesn't matter which we
      // choose. Ensure revision_default isn't set on our revision in
      // config_entity_revisions_revision - $was_default can return FALSE
      // even when that value is 1, and that will cause the content moderation
      // module (which does look at that field) to throw an exception.
      $this->connection->update("config_entity_revisions_revision")
        ->condition('id', $revision->id())
        ->fields(['revision_default' => 0])
        ->execute();
      $revision_to_use = ($revisions[0] == $this->revision->getRevisionId()) ?
        $revisions[1] : $revisions[0];
      $new_default = $this->configEntityRevisionsStorage->loadRevision($revision_to_use);
      $new_default->enforceIsNew(FALSE);
      $new_default->isDefaultRevision(TRUE);
      $new_default->save();
    }

    $this->entityTypeManager
      ->getStorage('config_entity_revisions')
      ->deleteRevision($revision->getRevisionId());
  }

  /**
   * Delete revisions when a config entity is deleted.
   *
   * @param ConfigEntityRevisionsInterface $configEntity
   *   The configuration entity being deleted.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteRevisions(ConfigEntityRevisionsInterface $configEntity) {
    $contentEntity = $configEntity->getContentEntity();

    if ($contentEntity) {
      $configEntity->contentEntityStorage()->delete([$contentEntity]);
    }
  }

  /**
   * Load a particular revision of a config entity.
   *
   * @param int $revision
   *   The revision ID to load.
   * @param mixed $entity
   *   The entity type to load.
   *
   * @return mixed
   *   The loaded revision or NULL.
   */
  public function loadConfigEntityRevision($revision = NULL, $entity = '') {
    $configEntityName = $this->configEntityName();
    $storage = $this->entityTypeManager->getStorage($configEntityName);

    if (!$entity) {
      try {
        $match = $this->router->matchRequest($this->currentRequest);
      }
      catch (\Exception $exception) {
        $match = [];
      }
      $entity = $match[$configEntityName] ?? NULL;
    }

    if ($revision) {
      $revisionsEntity = $this->entityTypeManager->getStorage('config_entity_revisions')
        ->loadRevision($revision);

      if ($revisionsEntity) {
        $entity = $this->serialiser->deserialize(
          $revisionsEntity->get('configuration')->value, $storage->getEntityClass(),
          'json');

        $entity->set('enforceIsNew', FALSE);
        $loaded = [$entity];
        $entity::postLoad($storage, $loaded);
      }
    }

    if (is_string($entity)) {
      $entity = $storage->load($entity);
    }

    return $entity;
  }

  /**
   * Check whether a particular revision of a config entity should be updated.
   *
   * @param int $revision
   *   The default revision ID.
   * @param mixed $entity
   *   The entity type to load.
   *
   * @return mixed
   *   The loaded revision or NULL.
   */
  public function checkUpdateRevision($revision = NULL, $entity = '') {
    $result = $this->loadConfigEntityRevision($revision, $entity);
    $newerRevisions = $result->getLaterRevisions();
    /** @var \Drupal\config_entity_revisions\Entity\ConfigEntityRevisions $newerRevision */
    foreach ($newerRevisions as $newerRevision) {
      $form = $this->entityTypeManager()
        ->getStorage('webform')
        ->loadRevision($newerRevision->getRevisionId());
      $revisionSettings = $form->getThirdPartySettings('webform_revisions');
      if (empty($revisionSettings['auto_update'])) {
        continue;
      }
      if (!empty($revisionSettings['auto_update_exclusions']) &&
        in_array($revision, $revisionSettings['auto_update_exclusions'])) {
        continue;
      }

      $result = $form;
      break;
    }
    return $result->getRevisionId();
  }

}
