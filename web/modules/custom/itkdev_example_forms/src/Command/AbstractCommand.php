<?php

declare(strict_types=1);

namespace Drupal\itkdev_example_forms\Command;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\File\FileSystemInterface;
use Drupal\webform\WebformEntityStorageInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;

// phpcs:disable Drupal.Commenting.ClassComment.Missing
abstract class AbstractCommand extends Command {
  use AutowireTrait;

  protected const WEBFORM_ID_PREFIX = 'itkdev_ex_';

  /**
   * The webform storage.
   */
  protected readonly WebformEntityStorageInterface $webformStorage;

  /**
   * The example modules.
   *
   * @var \Drupal\Core\Extension\Extension[]
   */
  protected array $exampleModules;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    protected readonly ConfigManagerInterface $configManager,
    protected readonly ModuleHandler $moduleHandler,
    protected readonly FileSystemInterface $fileSystem,
  ) {
    parent::__construct();

    /** @var \Drupal\webform\WebformEntityStorageInterface $webformStorage */
    $webformStorage = $entityTypeManager->getStorage('webform');
    $this->webformStorage = $webformStorage;
    $this->exampleModules = [];
    foreach ($this->moduleHandler->getModuleList() as $module) {
      if ($this->isExampleModule($module)) {
        $this->exampleModules[$module->getName()] = $module;
      }
    }
  }

  /**
   * Is example module?
   */
  protected function isExampleModule(string|Extension $module): bool {
    $name = is_string($module) ? $module : $module->getName();

    return str_starts_with($name, self::WEBFORM_ID_PREFIX);
  }

  /**
   * Get example module name for a webform ID.
   *
   * @throws \Drupal\Core\Extension\Exception\UnknownExtensionException
   *   If no example module can be found.
   */
  protected function getExampleModule(string $webformId): Extension {
    foreach ($this->exampleModules as $moduleName => $module) {
      if (str_starts_with($webformId, $moduleName)) {
        return $module;
      }
    }

    throw new UnknownExtensionException(dt('Cannot find example module for webform %webform_id', [
      '%webform_id' => $webformId,
    ]));
  }

  /**
   * Get config name for a webform ID.
   */
  protected function getWebformConfigName(string $webformId): string {
    return 'webform.webform.' . $webformId;
  }

  /**
   * Is webform config name?
   */
  protected function isWebformConfigName(string $configName): bool {
    return str_starts_with($configName, 'webform.webform.');
  }

  /**
   * Is webform config name?
   */
  protected function getWebformId(string $configName): string {
    return substr($configName, strlen('webform.webform.'));
  }

  /**
   * Request an example module.
   */
  protected function requestExampleModule(?string $moduleName, SymfonyStyle $io): Extension {
    if (!$moduleName) {
      $choices = [];
      foreach ($this->exampleModules as $module) {
        $choices[$module->getName()] = $module->getName();
      }
      $moduleName = $io->choice('Module?', $choices);
    }

    try {
      $module = $this->moduleHandler->getModule($moduleName);
    }
    catch (UnknownExtensionException $e) {
      throw new InvalidArgumentException(dt('Invalid module: %module', ['%module' => $moduleName]));
    }

    if (!$this->isExampleModule($module->getName())) {
      throw new RuntimeException(dt('Module %module is not an example module', ['%module' => $moduleName]));
    }

    return $module;
  }

}
