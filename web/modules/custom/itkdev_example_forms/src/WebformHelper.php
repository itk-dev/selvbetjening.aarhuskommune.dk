<?php

namespace Drupal\itkdev_example_forms;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\webform\WebformEntityStorageInterface;
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

  /**
   * The webform storage.
   */
  private WebformEntityStorageInterface $webformStorage;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $nodeStorage = $entityTypeManager->getStorage('node');
    $this->nodeStorage = $nodeStorage;
    /** @var \Drupal\webform\WebformEntityStorageInterface $webformStorage */
    $webformStorage = $entityTypeManager->getStorage('webform');
    $this->webformStorage = $webformStorage;
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
  public function loadWebformPage(string|WebformInterface $webform): ?NodeInterface {
    $webformId = is_string($webform) ? $webform : $webform->id();

    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = $this->nodeStorage->loadByProperties([
      'type' => self::NODE_TYPE_WEBFORM,
      self::FIELD_WEBFORM => $webformId,
    ]);

    return reset($nodes) ?: NULL;
  }

  /**
   * Load webforms by ID prefix.
   *
   * @param string $prefix
   *   The webform ID prefix.
   *
   * @return \Drupal\webform\WebformInterface[]
   *   The webforms
   */
  public function loadWebforms(string $prefix): array {
    $webforms = $this->webformStorage->loadMultiple();

    return array_filter($webforms,
      static fn(WebformInterface $webform): bool => str_starts_with($webform->id(), $prefix));
  }

}
