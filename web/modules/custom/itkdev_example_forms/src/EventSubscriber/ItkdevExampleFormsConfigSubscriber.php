<?php

declare(strict_types=1);

namespace Drupal\itkdev_example_forms\EventSubscriber;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageTransformEvent;
use Drupal\itkdev_example_forms\ModuleHelper;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The event subscriber preventing example wbeforms modules to be exported.
 *
 * Apart from the implementation of the constructor and getExcludedModules
 * all code in this class is a verbatim copy of
 * web/core/lib/Drupal/Core/EventSubscriber/ExcludedModulesEventSubscriber.php
 * which handles $settings['config_exclude_modules'].
 *
 * @see ExcludedModulesEventSubscriber
 */
final class ItkdevExampleFormsConfigSubscriber implements EventSubscriberInterface {

  /**
   * Constructs an ItkdevExampleFormsSubscriber object.
   */
  public function __construct(
    #[Autowire(service: 'config.storage')]
    private readonly StorageInterface $activeStorage,
    private readonly ConfigManagerInterface $manager,
    private readonly ModuleHelper $moduleHelper,
  ) {
  }

  /**
   * Get the modules to exclude.
   *
   * @return string[]
   *   An array of module names.
   */
  private function getExcludedModules() {
    // We want to exclude all examples modules ….
    $moduleNames = array_keys($this->moduleHelper->getExampleModules());
    // … and the base module …
    $moduleNames[] = 'itkdev_example_forms';
    // … and our look up mock.
    $moduleNames[] = 'os2web_datalookup_mock';

    return array_unique($moduleNames);
  }

  // The rest is copied from web/core/lib/Drupal/Core/EventSubscriber/ExcludedModulesEventSubscriber.php.

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // React early on export and late on import.
    return [
      'config.transform.import' => ['onConfigTransformImport', -500],
      'config.transform.export' => ['onConfigTransformExport', 500],
    ];
  }

  /**
   * Transform the storage which is used to import the configuration.
   *
   * Make sure excluded modules are not uninstalled by adding them and their
   * config to the storage when importing configuration.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The transformation event.
   */
  public function onConfigTransformImport(StorageTransformEvent $event) {
    $storage = $event->getStorage();
    if (!$storage->exists('core.extension')) {
      // If the core.extension config is not present there is nothing to do.
      // This means that probably the storage is empty or non-functional.
      return;
    }

    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $this->activeStorage->getAllCollectionNames()) as $collectionName) {
      $collection = $storage->createCollection($collectionName);
      $activeCollection = $this->activeStorage->createCollection($collectionName);
      foreach ($this->getDependentConfigNames() as $configName) {
        if (!$collection->exists($configName) && $activeCollection->exists($configName)) {
          // Make sure the config is not removed if it exists.
          $collection->write($configName, $activeCollection->read($configName));
        }
      }
    }

    $extension = $storage->read('core.extension');
    $existing = $this->activeStorage->read('core.extension');

    $modules = $extension['module'];
    foreach ($this->getExcludedModules() as $module) {
      if (array_key_exists($module, $existing['module'])) {
        // Set the modules weight from the active store.
        $modules[$module] = $existing['module'][$module];
      }
    }

    // Sort the extensions.
    $extension['module'] = module_config_sort($modules);
    // Set the modified extension.
    $storage->write('core.extension', $extension);
  }

  /**
   * Transform the storage which is used to export the configuration.
   *
   * Make sure excluded modules are not exported by removing all the config
   * which depends on them from the storage that is exported.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The transformation event.
   */
  public function onConfigTransformExport(StorageTransformEvent $event) {
    $storage = $event->getStorage();
    if (!$storage->exists('core.extension')) {
      // If the core.extension config is not present there is nothing to do.
      // This means some other process has rendered it non-functional already.
      return;
    }

    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $storage->getAllCollectionNames()) as $collectionName) {
      $collection = $storage->createCollection($collectionName);
      foreach ($this->getDependentConfigNames() as $configName) {
        $collection->delete($configName);
      }
    }

    $extension = $storage->read('core.extension');
    // Remove all the excluded modules from the extensions list.
    $extension['module'] = array_diff_key($extension['module'], array_flip($this->getExcludedModules()));

    $storage->write('core.extension', $extension);
  }

  /**
   * Get all the configuration which depends on one of the excluded modules.
   *
   * @return string[]
   *   An array of configuration names.
   */
  private function getDependentConfigNames() {
    $modules = $this->getExcludedModules();

    $dependencyManager = $this->manager->getConfigDependencyManager();
    $config = [];

    // Find all the configuration depending on the excluded modules.
    foreach ($modules as $module) {
      foreach ($dependencyManager->getDependentEntities('module', $module) as $dependent) {
        $config[] = $dependent->getConfigDependencyName();
      }
      $config = array_merge($config, $this->activeStorage->listAll($module . '.'));
    }

    // Find all configuration that depends on the configuration found above.
    foreach ($this->manager->findConfigEntityDependencies('config', array_unique($config)) as $dependent) {
      $config[] = $dependent->getConfigDependencyName();
    }

    return array_unique($config);
  }

}
