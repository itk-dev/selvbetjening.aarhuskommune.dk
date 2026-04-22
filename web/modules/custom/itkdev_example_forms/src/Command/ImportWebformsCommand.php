<?php

declare(strict_types=1);

namespace Drupal\itkdev_example_forms\Command;

use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// phpcs:disable Drupal.Commenting.ClassComment.Missing
#[AsCommand(
  name: 'itkdev-example-forms:webforms:import',
  description: 'Import example webforms',
)]
final class ImportWebformsCommand extends AbstractCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->addArgument('module', InputArgument::OPTIONAL, 'The module')
      ->addArgument('webform-id', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'The webform IDs')
      ->addOption('diff', NULL, InputOption::VALUE_NONE);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);

    $moduleName = $input->getArgument('module');
    if (!$moduleName && !$input->isInteractive()) {
      throw new RuntimeException(dt('Module name cannot be empty when running interactively.'));
    }

    if (!$moduleName) {
      if (!$input->isInteractive()) {
        throw new RuntimeException(dt('Module name cannot be empty when running interactively.'));
      }

      $choices = [];
      foreach ($this->exampleModules as $module) {
        $choices[$module->getName()] = $module->getName();
      }
      $moduleName = $io->choice('Module?', $choices);
    }

    $webformIds = $input->getArgument('webform-id');
    $includeFile = function (string $filename) use (&$webformIds) {
      if (empty($webformIds)) {
        return TRUE;
      }

      foreach ($webformIds as $webformId) {
        if (str_contains($filename, $webformId)) {
          return TRUE;
        }
      }

      return FALSE;
    };

    try {
      $module = $this->moduleHandler->getModule($moduleName);
    }
    catch (UnknownExtensionException $e) {
      throw new InvalidArgumentException(dt('Invalid module: %module.', ['%module' => $moduleName]));
    }

    $sourcePath = $module->getPath() . '/config/install';
    if (!is_dir($sourcePath)) {
      throw new RuntimeException(dt('Source path %source_path does not exist.', ['%source_path' => $sourcePath]));
    }

    // Copy webform config files to temporary directory.
    $tempSource = sys_get_temp_dir() . '/itkdev_example_forms/' . uniqid();
    try {
      $this->fileSystem->prepareDirectory($tempSource, options: FileSystemInterface::CREATE_DIRECTORY);
      foreach ($this->fileSystem->scanDirectory($sourcePath, '/\.yml$/') as $file) {
        if (!$includeFile($file->filename)) {
          continue;
        }
        $this->fileSystem->copy($file->uri, $tempSource, fileExists: FileExists::Replace);
      }

      $configImportInput = new ArrayInput([
        // The command name is passed as first argument.
        'command' => 'config:import',
        '--partial' => TRUE,
        '--source' => $tempSource,
        '--diff' => (bool) $input->getOption('diff'),
      ]);

      if ($io->isDebug()) {
        $io->writeln(['import input', var_export($configImportInput->getOptions(), TRUE)]);
      }

      $configImportInput->setInteractive($input->isInteractive());

      return $this->getApplication()->doRun($configImportInput, $output);
    } finally {
      if (is_dir($tempSource)) {
        $this->fileSystem->deleteRecursive($tempSource);
      }
    }

  }

}
