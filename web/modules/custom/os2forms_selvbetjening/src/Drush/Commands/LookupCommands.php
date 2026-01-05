<?php

namespace Drupal\os2forms_selvbetjening\Drush\Commands;

use Drupal\os2web_datalookup\Plugin\DataLookupManager;
use Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupCprInterface;
use Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DatafordelerCVR;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Lookup commands.
 */
final class LookupCommands extends DrushCommands {

  /**
   * Constructor.
   */
  public function __construct(
    private readonly DataLookupManager $dataLookupManager,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.os2web_datalookup'),
    );
  }

  /**
   * Look up CPR.
   */
  #[CLI\Command(name: 'os2forms-selvbetjening:look-up:cpr')]
  #[CLI\Usage(name: 'os2forms-selvbetjening:look-up:cpr --help', description: 'Look up CPR.')]
  public function lookUpCpr(
    string $cpr,
    array $options = [
      'dump-configuration' => FALSE,
    ],
  ) {
    try {
      $instance = $this->dataLookupManager->createDefaultInstanceByGroup('cpr_lookup');
      assert($instance instanceof DataLookupCprInterface);

      if ($options['dump-configuration']) {
        $this->output()->writeln([
          Yaml::dump($instance->getConfiguration()),
        ]);
      }
      $result = $instance->lookup($cpr);

      if (!$result->isSuccessful()) {
        $this->output()->writeln($result->getErrorMessage());
      }
      else {
        $this->output()->write($result->getName());
      }
    }
    catch (\Exception $exception) {
      $this->output()->writeln($exception->getMessage());
    }
  }

  /**
   * Look up CVR.
   */
  #[CLI\Command(name: 'os2forms-selvbetjening:look-up:cvr')]
  #[CLI\Usage(name: 'os2forms-selvbetjening:look-up:cvr --help', description: 'Look up CVR.')]
  public function lookUpCvr(
    string $cvr,
    array $options = [
      'dump-configuration' => FALSE,
    ],
  ) {
    try {
      $instance = $this->dataLookupManager->createDefaultInstanceByGroup('cvr_lookup');
      assert($instance instanceof DatafordelerCVR);

      if ($options['dump-configuration']) {
        $this->output()->writeln([
          Yaml::dump($instance->getConfiguration()),
        ]);
      }
      $result = $instance->lookup($cvr);

      if (!$result->isSuccessful()) {
        $this->output()->writeln($result->getErrorMessage());
      }
      else {
        $this->output()->write($result->getName());
      }
    }
    catch (\Exception $exception) {
      $this->output()->writeln($exception->getMessage());
    }
  }

}
