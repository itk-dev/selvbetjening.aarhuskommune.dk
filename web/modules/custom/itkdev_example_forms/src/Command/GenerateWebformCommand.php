<?php

declare(strict_types=1);

namespace Drupal\itkdev_example_forms\Command;

use Composer\Console\Input\InputArgument;
use Composer\Console\Input\InputOption;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\webform\WebformEntityStorageInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// phpcs:disable Drupal.Commenting.ClassComment.Missing
#[AsCommand(
  name: 'itkdev-example-forms:webform:generate',
  description: 'Generate example webform',
)]
final class GenerateWebformCommand extends Command {
  use AutowireTrait;

  /**
   * The webform storage.
   */
  private readonly WebformEntityStorageInterface $webformStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    private readonly ModuleHandler $moduleHandler,
  ) {
    parent::__construct();
    $this->webformStorage = $entityTypeManager->getStorage('webform');
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->addArgument('module', InputArgument::OPTIONAL, 'The module')
      ->addArgument('webform-id', InputArgument::OPTIONAL, 'The webform ID')
      ->addOption('generate-id', NULL, InputOption::VALUE_NONE, 'Generate a random webform ID')
      ->addOption('title', NULL, InputOption::VALUE_REQUIRED, 'Webform title');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);

    $moduleName = $input->getArgument('module');
    if (!$moduleName) {
      $exampleModules = array_filter(
        $this->moduleHandler->getModuleList(),
        static fn(Extension $module): bool => str_starts_with($module->getName(), 'itkdev_ex_')
      );

      $choices = [];
      foreach ($exampleModules as $moduleName) {
        $choices[$moduleName->getName()] = $moduleName->getName();
      }
      $moduleName = $io->choice('Module?', $choices);
    }

    try {
      $module = $this->moduleHandler->getModule($moduleName);
    }
    catch (UnknownExtensionException $e) {
      throw new InvalidArgumentException(dt('Invalid module: %module', ['%module' => $moduleName]));
    }

    $webformId = $input->getArgument('webform-id');
    if (!$webformId && $input->getOption('generate-id')) {
      $webformId = uniqid();
    }
    while (empty(trim((string) $webformId))) {
      $webformId = $io->ask('Webform ID?');
    }

    $webformId = $module->getName() . '_' . $webformId;

    if ($this->webformStorage->load($webformId)) {
      throw new InvalidArgumentException(dt('Webform "%webform" already exists', ['%webform' => $webformId]));
    }

    $title = $input->getOption('title');
    while (empty(trim((string) $title))) {
      $title = $io->ask('Title?');
    }

    $webform = $this->webformStorage->create([
      'id' => $webformId,
      'title' => $title,
    ]);
    $webform->save();

    $io->success(dt('Webform "%webform" created', ['%webform' => $webformId]));
    $io->info($webform->toUrl('edit-form')->setAbsolute()->toString(TRUE)->getGeneratedUrl());

    return self::SUCCESS;
  }

}
