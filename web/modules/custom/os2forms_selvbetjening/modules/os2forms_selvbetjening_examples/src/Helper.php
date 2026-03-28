<?php

namespace Drupal\os2forms_selvbetjening_examples;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeStorageInterface;
use Drupal\taxonomy\TermStorageInterface;

/**
 * A helper class.
 */
class Helper {
  /**
   * Node form ids.
   *
   * @var array|string[]
   */
  private array $nodeFormIds = [
    'example_flow_step_2',
  ];

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  private NodeStorageInterface $nodeStorage;

  /**
   * The term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface|\Drupal\Core\Entity\EntityStorageInterface
   */
  private TermStorageInterface $termStorage;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private ConfigFactoryInterface $configFactory;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    $this->nodeStorage = $entityTypeManager->getStorage('node');
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
    $this->configFactory = $configFactory;
  }

  /**
   * Implements hook_install().
   */
  public function install() {
    // hook_install() semms to be called twice (!) so we clean up before
    // creating nodes.
    $this->deleteFormNodes();
    $this->createFormNodes();
    $this->updateAffiliations();
  }

  /**
   * Implements hook_uninstall().
   */
  public function uninstall() {
    $this->deleteFormNodes();
  }

  /**
   * Create form nodes.
   */
  private function createFormNodes(): void {
    foreach ($this->nodeFormIds as $formId) {
      $node = Node::create([
        'type' => 'webform',
        'title' => sprintf('Webform node for form %s', $formId),
        'webform' => [
          'target_id' => $formId,
        ],
        'status' => Node::PUBLISHED,
        'moderation_state' => 'published',
      ]);
      $node->save();

      if ('example_flow_step_2' === $formId) {
        $this->configFactory->getEditable('maestro.maestro_template.example_flow_page')
          ->set('tasks.example_flow_step_2.data.webform_nodes_attached_to', 'node/' . $node->id())
          ->save();
      }
    }
  }

  /**
   * Set affiliation on all flows.
   */
  private function updateAffiliations(): void {
    $terms = $this->termStorage->loadTree('user_affiliation');
    $affiliations = [];
    foreach ($terms as $term) {
      $affiliations[$term->tid] = (string) $term->tid;
    }

    $formIds = [
      'example_flow_step_1',
      'example_flow_step_2',
    ];
    foreach ($formIds as $formId) {
      $this->configFactory->getEditable('webform.webform.' . $formId)
        ->set('third_party_settings.os2forms_permissions_by_term.settings', $affiliations)
        ->save();
    }

    $flowIds = [
      'example_flow',
      'example_flow_page',
    ];
    foreach ($flowIds as $flowId) {
      $this->configFactory->getEditable('maestro.maestro_template.' . $flowId)
        ->set('third_party_settings.os2forms_permissions_by_term.maestro_template_permissions_by_term_settings', $affiliations)
        ->save();
    }
  }

  /**
   * Delete form nodes.
   */
  private function deleteFormNodes(): void {
    $nodeIds = $this->nodeStorage
      ->getQuery()
      ->condition('webform', $this->nodeFormIds)
      ->accessCheck(FALSE)
      ->execute();
    $nodes = $this->nodeStorage->loadMultiple($nodeIds);
    foreach ($nodes as $node) {
      $node->delete();
    }
  }

}
