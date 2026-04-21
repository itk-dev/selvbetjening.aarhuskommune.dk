<?php

namespace Drupal\itkdev_booking\Helper;

/**
 *
 */
class SampleDataHelper {

  /**
   * Get sample data from local file.
   *
   * @param $sampleName
   *   Name of the same to serve.
   *
   * @return array
   *   The sample data requested.
   *
   * @throws \JsonException
   */
  public static function getSampleData($sampleName): array {
    $stringData = file_get_contents(__DIR__ . '/../../sampleData/' . $sampleName . '.json');
    return json_decode($stringData, TRUE, 512, JSON_THROW_ON_ERROR);
  }

}
