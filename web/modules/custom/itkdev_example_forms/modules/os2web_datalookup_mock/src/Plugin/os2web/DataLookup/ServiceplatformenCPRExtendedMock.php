<?php

namespace Drupal\os2web_datalookup_mock\Plugin\os2web\DataLookup;

use Drupal\os2web_datalookup\Plugin\os2web\DataLookup\ServiceplatformenCPRExtended;
use Symfony\Component\Yaml\Yaml;

/**
 * Serviceplatformen CPR Extended Mock.
 */
class ServiceplatformenCPRExtendedMock extends ServiceplatformenCPRExtended {

  /**
   * {@inheritdoc}
   */
  public function isReady(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function query(string $method, array $request): array {
    try {
      $id = $request['PNR'] ?? NULL;

      // https://symfony.com/doc/current/components/yaml.html#parsing-php-constants
      $data = Yaml::parseFile(__DIR__ . '/' . $method . '.yaml', flags: Yaml::PARSE_CONSTANT);
      if (!isset($data[$id])) {
        throw new \RuntimeException('Invalid CPR: ' . $id);
      }

      $result = $data[$id] + ['status' => TRUE];

      // Convert some (JSON) values to objects.
      foreach ([
        'persondata',
        'adresse',
        'relationer',
      ] as $key) {
        if (isset($result[$key]) && is_array($result[$key])) {
          $result[$key] = json_decode(json_encode($result[$key]));
        }
      }

      return $result;
    }
    catch (\Exception $exception) {
      return [
        'status' => FALSE,
        'error' => $exception->getMessage(),
      ];
    }
  }

}
