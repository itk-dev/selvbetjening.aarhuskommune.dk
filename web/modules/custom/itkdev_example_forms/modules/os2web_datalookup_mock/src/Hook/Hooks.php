<?php

namespace Drupal\os2web_datalookup_mock\Hook;

use Drupal\os2web_datalookup_mock\Plugin\os2web\DataLookup\ServiceplatformenCPRExtendedMock;

/**
 * Hook implementations.
 */
class Hooks {

  /**
   * Implements os2web_datalookup_info_alter().
   *
   * @todo Add #[Hook('os2web_datalookup_info_alter')] when upgrading to
   * Drupal 11 (cf. https://www.drupal.org/node/3442349).
   */
  public function os2webDatalookupInfoAlter(array &$data): void {
    // Inject our mock services.
    $data['serviceplatformen_cpr_extended']['class'] = ServiceplatformenCPRExtendedMock::class;
  }

}
