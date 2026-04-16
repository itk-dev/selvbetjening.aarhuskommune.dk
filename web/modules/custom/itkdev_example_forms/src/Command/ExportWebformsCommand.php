<?php

declare(strict_types=1);

namespace Drupal\itkdev_example_forms\Command;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

// phpcs:disable Drupal.Commenting.ClassComment.Missing
#[AsCommand(
  name: 'itkdev-example-forms:webforms:export',
  description: 'Export example webforms',
)]
final class ExportWebformsCommand extends Command {
  use AutowireTrait;

  private const CONFIG_NAME_PATTERNS = [
    // Regexp => module name.
    '/^webform\.webform\.itkdev_ex_/' => 'itkdev_ex_misc',
    '/^webform\.webform\.itkdev_ex_mitid_/' => 'itkdev_ex_mitid',
  ];

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
    #[Autowire(service: 'extension.list.module')]
    private readonly ModuleExtensionList $moduleExtensionList,
    private readonly FileSystemInterface $fileSystem,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);

    if (!$io->confirm('Really export all example webforms?', !$input->isInteractive())) {
      return self::FAILURE;
    }

    $configNamePatterns = array_keys(self::CONFIG_NAME_PATTERNS);
    if ($output->isVerbose()) {
      $io->info(dt('Exporting webforms with IDs matching one of %patterns', [
        '%patterns' => implode(',', $configNamePatterns),
      ]));
    }

    $configFactory = $this->configManager->getConfigFactory();
    $configNames = array_values(
      array_filter(
        $configFactory->listAll(),
        static fn(string $name): bool => !empty(array_filter(
          array_map(
            static fn(string $pattern) => preg_match($pattern, $name),
            $configNamePatterns
          )
        )),
      )
    );

    foreach ($configNames as $configName) {
      $moduleName = $this->getModuleName($configName);
      try {
        $moduleDir = $this->moduleExtensionList->getPath($moduleName);
      }
      catch (UnknownExtensionException $exception) {
        $io->error($exception->getMessage());
        continue;
      }
      $targetDir = $moduleDir . '/config/install';
      if (!is_dir($targetDir)) {
        $this->fileSystem->mkdir($targetDir, recursive: TRUE);
      }
      if ($output->isVerbose()) {
        $io->info(dt('Exporting %config_name to module %module_name', [
          '%config_name' => $configName,
          '%module_name' => $moduleName,
        ]));
      }

      foreach ($configNames as $name) {
        $targetName = $targetDir . '/' . $name . '.yml';

        if ($output->isVerbose()) {
          $io->section($name);
        }

        $config = $configFactory->getEditable($name);
        foreach (static::$configKeysToClear as $key) {
          if ($output->isVerbose()) {
            $io->writeln(dt('Clearing key %config_key', ['%config_key' => $key]));
          }
          $config->clear($key);
        }

        $dependencies = $config->get('dependencies');
        $enforcedModules = $dependencies['enforced']['module'] ?? [];
        $dependencies['enforced']['module'] = array_unique((array) $enforcedModules + [$moduleName]);
        $config->set('dependencies', $dependencies);

        // @todo (How) Can we use the config manager (or factory) to do this?
        file_put_contents($targetName, Yaml::encode($config->get()));
        $io->success(dt('Config %config_name written to %file', [
          '%config_name' => $configName,
          '%file' => $targetName,
        ]));
      }
    }

    return self::SUCCESS;
  }

  /**
   * Get module name form config name.
   */
  private function getModuleName(string $configName): string {
    $bestMatch = NULL;

    foreach (self::CONFIG_NAME_PATTERNS as $pattern => $moduleName) {
      if (preg_match($pattern, $configName, $matches)) {
        $match = $matches[0];
        if (strlen($match) > strlen($bestMatch ?? '')) {
          $bestMatch = $moduleName;
        }
      }
    }

    return $bestMatch;
  }

}
