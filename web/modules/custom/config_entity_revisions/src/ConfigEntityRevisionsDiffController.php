<?php

namespace Drupal\config_entity_revisions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\diff\Controller\PluginRevisionController;
use Drupal\diff\DiffEntityComparison;
use Drupal\diff\DiffLayoutManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ConfigEntityRevisionsDiffController.
 *
 * Compare revisions of an entity.
 */
class ConfigEntityRevisionsDiffController extends PluginRevisionController {

  /**
   * Constructs a PluginRevisionController object.
   *
   * @param \Drupal\diff\DiffEntityComparison $entity_comparison
   *   The diff entity comparison service.
   * @param \Drupal\diff\DiffLayoutManager $diff_layout_manager
   *   The diff layout service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(DiffEntityComparison $entity_comparison, DiffLayoutManager $diff_layout_manager, RequestStack $request_stack, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($entity_comparison, $diff_layout_manager, $request_stack);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('diff.entity_comparison'),
      $container->get('plugin.manager.diff.layout'),
      $container->get('request_stack'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns a table which shows the differences between two entity revisions.
   *
   * @param string $config_entity_type
   *   The type of entity to compare.
   * @param mixed $config_entity_id
   *   The ID of the entity.
   * @param int $left_revision
   *   Id of the revision from the left.
   * @param int $right_revision
   *   Id of the revision from the right.
   * @param string $filter
   *   If $filter == 'raw' raw text is compared (including html tags)
   *   If $filter == 'raw-plain' markdown function is applied to the text before
   *   comparison.
   *
   * @return array
   *   Table showing the diff between the two node revisions.
   */
  public function compareConfigEntityRevisions($config_entity_type, $config_entity_id, $left_revision, $right_revision, $filter) {
    $entity_type = $this->entityTypeManager
      ->getDefinition($config_entity_type);
    $class = $entity_type->get('class');
    if (!in_array('Drupal\config_entity_revisions\ConfigEntityRevisionsInterface', class_implements($class))) {
      return [];
    }
    $storage = $this->entityTypeManager
      ->getStorage($config_entity_type);

    $route_match = \Drupal::routeMatch();
    $left_revision = $storage->loadRevision($left_revision);
    $right_revision = $storage->loadRevision($right_revision);

    $build = $this->compareEntityRevisions($route_match, $left_revision, $right_revision, $filter);
    return $build;
  }

}
