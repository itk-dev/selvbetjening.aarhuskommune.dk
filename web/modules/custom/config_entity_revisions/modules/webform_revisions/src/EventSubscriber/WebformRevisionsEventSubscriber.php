<?php

namespace Drupal\webform_revisions\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\StorageTransformEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for webform revisions events related to config export.
 */
class WebformRevisionsEventSubscriber implements EventSubscriberInterface {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::STORAGE_TRANSFORM_IMPORT][] = ['onImportTransform'];
    $events[ConfigEvents::STORAGE_TRANSFORM_EXPORT][] = ['onExportTransform'];
    return $events;
  }

  /**
   * The storage is transformed for importing.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The config storage transform event.
   */
  public function onImportTransform(StorageTransformEvent $event) {
    /** @var \Drupal\Core\Config\StorageInterface $storage */
    $storage = $event->getStorage();
    $webforms = array_filter($storage->listAll(), function ($key) {
      return (substr($key, 0, 16) == 'webform.webform.');
    });
    $formStorage = $this->entityTypeManager->getStorage('webform');
    foreach ($webforms as $webform) {
      $form = $formStorage->load(substr($webform, 16));

      if (!$form || empty($form->getThirdPartySettings('webform_revisions'))) {
        continue;
      }

      $data = $storage->read($webform);

      if (empty($data['third_party_settings'])) {
        $data['third_party_settings'] = [];
      }
      $data['third_party_settings']['webform_revisions'] = $form->getThirdPartySettings('webform_revisions');
      $storage->write($webform, $data);
    }
  }

  /**
   * The storage is transformed for exporting.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The config storage transform event.
   */
  public function onExportTransform(StorageTransformEvent $event) {
    /** @var \Drupal\Core\Config\StorageInterface $storage */
    $storage = $event->getStorage();
    $webforms = array_filter($storage->listAll(), function ($key) {
      return (substr($key, 0, 16) == 'webform.webform.');
    });
    foreach ($webforms as $webform) {
      $data = $storage->read($webform);
      unset($data['third_party_settings']['webform_revisions']);
      $storage->write($webform, $data);
    }
  }

}
