<?php

declare(strict_types=1);

namespace Drupal\itkdev_example_forms\Command;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// phpcs:disable Drupal.Commenting.ClassComment.Missing
#[AsCommand(
  name: 'itkdev-example-forms:webforms:export',
  description: 'Export example webforms',
)]
final class ExportWebformsCommand extends Command {
  use AutowireTrait;

  private const WEBFORM_ID_PREFIX = 'itkdev_ex_';

  /**
   * The example modules.
   *
   * @var Extension[]
   */
  private array $exampleModules;

  /**
   * The config keys to clear.
   */
  private static array $configKeysToClear = [
    'uuid',
    '_core',
    'third_party_settings.webform_revisions',
    // 'third_party_settings.os2forms_permissions_by_term',
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct(
    private readonly ConfigManagerInterface $configManager,
    private readonly ModuleHandler $moduleHandler,
    private readonly FileSystemInterface $fileSystem,
  ) {
    parent::__construct();

    $this->exampleModules = [];
    foreach ($this->moduleHandler->getModuleList() as $module) {
      if ($this->isExampleModule($module)) {
        $this->exampleModules[$module->getName()] = $module;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);

      if (!$io->confirm('Really export all example webforms?', !$input->isInteractive())) {
        return self::FAILURE;
      }


    if ($output->isVerbose()) {
$io->info(dt('Exporting webforms with IDs starting with %prefix', ['%prefix' => self::WEBFORM_ID_PREFIX,]));
}

    $configFactory = $this->configManager->getConfigFactory();
    $webformIds = array_values(
      array_map(
        static fn (string $configName): string => substr($configName, 16),
        array_filter(
          $configFactory->listAll(),
          static fn(string $name): bool => str_starts_with($name, 'webform.webform.'.self::WEBFORM_ID_PREFIX),
        )
      )
    );

    if ($output->isVerbose()) {
      $io->info(array_merge(
        [dt('Exporting webforms with IDs')],
        $webformIds
      ));
    }

    foreach ($webformIds as $webformId) {
      $io->section($webformId);

      try {
        $module = $this->getModule($webformId);
      }
      catch (UnknownExtensionException $exception) {
        $io->error($exception->getMessage());
        continue;
      }

      $targetDir = $module->getPath() . '/config/install';
      if (!is_dir($targetDir)) {
        $this->fileSystem->mkdir($targetDir, recursive: TRUE);
      }

      if ($output->isVerbose()) {
        $io->info(dt('Exporting %config_name to module %module_name', [
          '%config_name' => $webformId,
          '%module_name' => $module->getName(),
        ]));
      }

      $config = $configFactory->getEditable($this->getConfigName($webformId));
      foreach (static::$configKeysToClear as $key) {
        if ($output->isVerbose()) {
          $io->writeln(dt('Clearing key %config_key', ['%config_key' => $key]));
        }
        $config->clear($key);
      }

      $dependencies = $config->get('dependencies');
      $enforcedModules = $dependencies['enforced']['module'] ?? [];
      $dependencies['enforced']['module'] = array_unique((array) $enforcedModules + [$module->getName()]);
      $config->set('dependencies', $dependencies);
      $config->save();

      $targetName = $targetDir . '/' . $config->getName() . '.yml';
      // @todo (How) Can we use the config manager (or factory) to do this?
      file_put_contents($targetName, Yaml::encode($config->get()));
      $io->success(dt('Webform %config_name exported to %file', [
        '%config_name' => $webformId,
        '%file' => $targetName,
      ]));
    }

    return self::SUCCESS;
  }

  private function isExampleModule(string|Extension $module): bool {
    $name = is_string($module) ? $module : $module->getName();

    return str_starts_with($name, self::WEBFORM_ID_PREFIX);
  }

  /**
   * Get module name form config name.
   */
  private function getModule(string $webformId): Extension {
    foreach ($this->exampleModules as $moduleName => $module) {
      if (str_starts_with($webformId, $moduleName)) {
        return $module;
      }
    }

    throw new UnknownExtensionException(dt('Cannot find example module for webform %webform_id', [
      '%webform_id' => $webformId,
    ]));
  }

  private function getConfigName(string $webformId): string {
    return 'webform.webform.'.$webformId;
  }

}
