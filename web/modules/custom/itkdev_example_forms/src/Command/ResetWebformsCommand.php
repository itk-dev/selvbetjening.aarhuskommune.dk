<?php

declare(strict_types=1);

namespace Drupal\itkdev_example_forms\Command;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ModuleHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// phpcs:disable Drupal.Commenting.ClassComment.Missing
#[AsCommand(
  name: 'itkdev-example-forms:webforms:reset',
  description: 'Reset example webforms',
)]
final class ResetWebformsCommand extends Command {
  use AutowireTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    private readonly ConfigManagerInterface $configManager,
    private readonly ModuleHandler $moduleHandler,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this->addArgument('webform-id', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'The webform IDs');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);

    $ids = $input->getArgument('webform-id');

    if (!$io->confirm(1 === count($ids)
      ? dt('Really reset the %id webform?', ['%id' => $ids[0]])
      : dt('Really reset %count webforms?', ['%count' => count($ids)]),
      !$input->isInteractive())) {
      return self::FAILURE;
    }

    $configFactory = $this->configManager->getConfigFactory();

    foreach ($ids as $id) {
      $configName = 'webform.webform.' . preg_replace('/^webform\.webform\./', '', $id);
      $config = $configFactory->getEditable($configName);
      $modules = (array) $config->get('dependencies.enforced.module');
      $moduleName = reset($modules);

      if (!$moduleName) {
        throw new \RuntimeException(dt('Cannot find module for webform %webform_id', ['%webform_id' => $id]));
      }

      try {
        $module = $this->moduleHandler->getModule($moduleName);
      }
      catch (UnknownExtensionException $exception) {
        $io->error($exception->getMessage());
        continue;
      }

      $filename = $module->getPath() . '/config/install/' . $configName . '.yml';
      if (!file_exists($filename)) {
        throw new \RuntimeException(dt('Config file %config_file does not exist', ['%config_file' => $filename]));
      }
      $data = Yaml::decode(file_get_contents($filename));
      $config->setData($data);
      $config->save();

      $io->success(dt('Config %config_name reset.', ['%config_name' => $configName]));
    }

    return self::SUCCESS;
  }

}
