<?php

declare(strict_types=1);

namespace Drupal\itkdev_example_forms\Command;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\File\FileSystemInterface;
use Drupal\itkdev_example_forms\ModuleHelper;
use Drupal\webform\WebformEntityStorageInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;

// phpcs:disable Drupal.Commenting.ClassComment.Missing
abstract class AbstractCommand extends Command {
  use AutowireTrait;

  protected const string WEBFORM_ID_PREFIX = 'itkdev_ex_';

  /**
   * The webform storage.
   */
  protected readonly WebformEntityStorageInterface $webformStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    protected readonly ConfigManagerInterface $configManager,
    protected readonly ModuleHelper $moduleHelper,
    protected readonly FileSystemInterface $fileSystem,
  ) {
    parent::__construct();

    /** @var \Drupal\webform\WebformEntityStorageInterface $webformStorage */
    $webformStorage = $entityTypeManager->getStorage('webform');
    $this->webformStorage = $webformStorage;
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
    $modules = $this->moduleHelper->getExampleModules();
    if (empty($modules)) {
      throw new RuntimeException('No example modules found.');
    }

    if (!$moduleName) {
      $choices = [];
      foreach ($modules as $module) {
        $choices[$module->getName()] = $module->getName();
      }
      $moduleName = $io->choice('Module?', $choices);
    }

    if (!isset($modules[$moduleName])) {
      throw new InvalidArgumentException(dt('Invalid module: %module', ['%module' => $moduleName]));
    }

    $module = $modules[$moduleName];

    if (!$this->moduleHelper->isExampleModule($module->getName())) {
      throw new RuntimeException(dt('Module %module is not an example module', ['%module' => $moduleName]));
    }

    return $module;
  }

}
