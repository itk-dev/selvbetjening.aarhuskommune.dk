<?php

namespace Drupal\views_revisions\ParamConverter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\ParamConverter\AdminPathConfigEntityConverter;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\views_revisions\ViewsRevisionsConfigTrait;
use Drupal\views_revisions\ViewsRevisionsUI;
use Symfony\Component\Routing\Route;

/**
 * Provides upcasting for a revisionable view entity to be used in the Views UI.
 *
 * Example:
 *
 * pattern: '/some/{view}/{revision_id}/and/{bar}'
 * options:
 *   parameters:
 *     view:
 *       type: 'entity:view'
 *       tempstore: TRUE
 *    revision_id:
 *      \+d
 *
 * The value for {view} will be converted to a view entity prepared for the
 * Views UI and loaded from the views temp store, but it will not touch the
 * value for {bar}.
 *
 * This class extends AdminPathConfigEntityConverter rather than ViewUIConverter
 * so that ViewUIConverter's converter can be replaced rather than extended
 * (we call the parent method). Other methods should remain the same as
 * ViewUIConverter.
 */
class ViewsRevisionsConverter extends AdminPathConfigEntityConverter implements ParamConverterInterface {

  use ViewsRevisionsConfigTrait;

  /**
   * Constructs a new ViewUIConverter.
   *
   * @param \Drupal\Core\Entity\entityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $tempStoreFactory
   *   The factory for the temp store object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The route admin context service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, protected SharedTempStoreFactory $tempStoreFactory, ConfigFactoryInterface $config_factory = NULL, AdminContext $admin_context = NULL, EntityRepositoryInterface $entityRepository = NULL) {
    parent::__construct($entity_manager, $config_factory, $admin_context, $entityRepository);
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {

    /** @var \Drupal\config_entity_revisions\ConfigEntityRevisionsInterface $entity */
    if (!$entity = parent::convert($value, $definition, $name, $defaults)) {
      return;
    }

    // Get the temp store for this variable if it needs one. Attempt to load the
    // view from the temp store, synchronize its status with the existing view,
    // and store the lock metadata.
    $store = $this->tempStoreFactory->get('views');

    /** @var ConfigEntityRevisionsEntityInterface $revisionsEntity */
    $revisionsEntity = NULL;

    // Get the config_entity_revisions entity, if one exists.
    $revisionsID = $entity->getContentEntityId();

    if ($revisionsID) {
      /** @var ConfigEntityRevisionsStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage('config_entity_revisions');

      if (!empty($defaults['revision_id']) && $defaults['revision_id'] != 'default') {
        $revisionsEntity = $storage->loadRevision($defaults['revision_id']);
      }
      elseif (array_key_exists('load_latest_revision', $definition)) {
        $revisionsEntity = $storage->getLatestRevision($revisionsID);
      }
      else {
        // If there's no latest published revision and the user has admin
        // permissions, get the latest revision.
        if (\Drupal::currentUser()
          ->hasPermission($entity->adminPermission()) &&
          is_null($revisionsEntity)) {
          $revisionsEntity = $storage->getLatestRevision($revisionsID);
        }
        else {
          $revisionsEntity = $storage->getLatestPublishedRevision($revisionsID);
        }
      }

      if (is_null($revisionsEntity)) {
        return NULL;
      }

      // Now that we know the revision ID to use, we can check whether we were
      // already editing it.
      $store_key = $value . '-' . $revisionsEntity->getRevisionId();
    }
    else {
      $store_key = $value;
    }

    if ($view = $store->get($store_key)) {
      if ($entity->status()) {
        $view->enable();
      }
      else {
        $view->disable();
      }
      $view->lock = $store->getMetadata($store_key);
    }
    else {
      if ($revisionsEntity) {
        // Otherwise, decorate the existing view for use in the UI.
        $entity = \Drupal::getContainer()->get('serializer')->deserialize(
          $revisionsEntity->get('configuration')->value,
          get_class($entity),
          'json');

        // The result of serialising and then deserialising is not an exact
        // copy of the original. This causes problems downstream if we don't fix
        // a few attributes here.
        $entity->set('settingsOriginal', $entity->get('settings'));
        $entity->set('enforceIsNew', FALSE);

        // Record the revision ID in the config entity so we can quickly and
        // easily access the revision record if needed (eg for edit form
        // revision message).
        $entity->setRevisionId($revisionsEntity->getRevisionId());
      }

      $view = new ViewsRevisionsUI($entity);
    }

    return $view;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (parent::applies($definition, $name, $route)) {
      return (!empty($definition['tempstore']) || !empty($route->getRequirement('revision_id'))
        && $definition['type'] === 'entity:view');
    }
    return FALSE;
  }

}
