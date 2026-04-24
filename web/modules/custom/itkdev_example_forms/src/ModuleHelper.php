<?php

namespace Drupal\itkdev_example_forms;

use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleExtensionList;

/**
 * Module helper.
 */
class ModuleHelper {
  private const WEBFORM_ID_PREFIX = 'itkdev_ex_';

  /**
   * Constructor.
   */
  public function __construct(
    private readonly ModuleExtensionList $moduleExtensionList,
  ) {
  }

  /**
   * Get example modules.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   The example modules.
   */
  public function getExampleModules(): array {
    $installedModules = array_intersect_key(
      $this->moduleExtensionList->getList(),
      $this->moduleExtensionList->getAllInstalledInfo()
    );

    $modules = [];
    foreach ($installedModules as $module) {
      if ($this->isExampleModule($module)) {
        $modules[$module->getName()] = $module;
      }
    }

    return $modules;
  }

  /**
   * Is example module?
   */
  public function isExampleModule(string|Extension $module): bool {
    $name = is_string($module) ? $module : $module->getName();

    return str_starts_with($name, self::WEBFORM_ID_PREFIX);
  }

  /**
   * Get example module name for a webform ID.
   *
   * @throws \Drupal\Core\Extension\Exception\UnknownExtensionException
   *   If no example module can be found.
   */
  public function getExampleModuleForWebform(string $webformId): Extension {
    foreach ($this->getExampleModules() as $moduleName => $module) {
      if (str_starts_with($webformId, $moduleName)) {
        return $module;
      }
    }

    throw new UnknownExtensionException(dt('Cannot find example module for webform %webform_id', [
      '%webform_id' => $webformId,
    ]));
  }

}
