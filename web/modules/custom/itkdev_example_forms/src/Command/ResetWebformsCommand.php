<?php

declare(strict_types=1);

namespace Drupal\itkdev_example_forms\Command;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Extension\ModuleExtensionList;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

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
    #[Autowire(service: 'extension.list.module')]
    private readonly ModuleExtensionList $moduleExtensionList,
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

      $moduleDir = $this->moduleExtensionList->getPath($moduleName);

      $filename = $moduleDir . '/config/install/' . $configName . '.yml';
      $data = Yaml::decode(file_get_contents($filename));
      $config->setData($data);
      $config->save();

      $io->success(dt('Config %config_name reset.', ['%config_name' => $configName]));
    }

    return self::SUCCESS;
  }

}
