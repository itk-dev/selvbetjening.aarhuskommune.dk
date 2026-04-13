<?php

namespace Drupal\os2web_datalookup_mock\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\os2web_datalookup_mock\Plugin\os2web\DataLookup\ServiceplatformenCPRExtendedMock;

/**
 * Hook implementations.
 */
class Hooks {

  /**
   * Implements os2web_datalookup_info_alter().
   */
  #[Hook('os2web_datalookup_info_alter')]
  public function os2webDatalookupInfoAlter(array &$data): void {
    // Inject our mock services.
    $data['serviceplatformen_cpr_extended']['class'] = ServiceplatformenCPRExtendedMock::class;
  }

}
