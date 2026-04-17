<?php

namespace Drupal\itkdev_example_forms;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\webform\WebformInterface;

/**
 * Webform helper.
 */
class WebformHelper {

  private const string NODE_TYPE_WEBFORM = 'webform';

  public const string FIELD_OS2FORMS_PERMISSIONS = 'field_os2forms_permissions';
  public const string FIELD_WEBFORM = 'webform';
  public const string CONTENT_MODERATION_STATE = 'moderation_state';
  public const string CONTENT_MODERATION_STATE_PUBLISHED = 'published';

  /**
   * The node storage.
   */
  private NodeStorageInterface $nodeStorage;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $nodeStorage = $entityTypeManager->getStorage('node');
    $this->nodeStorage = $nodeStorage;
  }

  /**
   * Create (or update) webform page.
   *
   * @param string|WebformInterface $webform
   *   The webform (ID)
   * @param array $values
   *   The page values.
   * @param bool $update
   *   If set, any existing page referencing the webform will be updated.
   *   Otherwise a new page is created.
   *
   * @return \Drupal\node\NodeInterface
   *   The created or updated node.
   */
  public function createWebformPage(string|WebformInterface $webform, array $values, bool $update = TRUE): NodeInterface {
    $webformId = is_string($webform) ? $webform : $webform->id();

    $page = $update ? $this->loadWebformPage($webformId) : NULL;
    if (NULL === $page) {
      $page = $this->nodeStorage->create([
        'type' => self::NODE_TYPE_WEBFORM,
        self::FIELD_WEBFORM => [
          'target_id' => $webformId,
          'default_data' => '',
          'status' => 'open',
          'open' => '',
          'close' => '',
        ],
      ]);
    }

    // Add some default values.
    $values += [
      self::CONTENT_MODERATION_STATE => self::CONTENT_MODERATION_STATE_PUBLISHED,
    ];

    foreach ($values as $name => $value) {
      $page->set($name, $value);
    }

    $page->save();

    return $page;
  }

  /**
   * Load webform page for a specific webform ID.
   */
  public function loadWebformPage(string $webformId): ?NodeInterface {
    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = $this->nodeStorage->loadByProperties([
      'type' => self::NODE_TYPE_WEBFORM,
      self::FIELD_WEBFORM => $webformId,
    ]);

    return reset($nodes) ?: NULL;
  }

}
