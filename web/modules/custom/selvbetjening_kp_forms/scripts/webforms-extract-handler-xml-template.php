<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

use Drupal\Component\Utility\NestedArray;

final class Script extends AbstractScript
{
  protected function run(): void
  {

    /** @var \Drupal\webform\WebformInterface[] $webforms */
    $webforms = \Drupal::entityTypeManager()->getStorage('webform')->loadMultiple($this->webformIds);
    foreach ($webforms as $webform) {
      $handler = $webform->getHandler($this->handlerId);

      $path = 'settings.distribution_object.xml_template';
      $xmlPath = __DIR__ . '/../handlers/webform.webform.' . $webform->id() . '.handlers.' . $handler->getHandlerId() . '.' . $path . '.xml.twig';

      $this->writeln($webform->label());
      $this->writeln($path);

      $configuration = $handler->getConfiguration();
      $xml = $configuration['settings']['distribution_object']['xml_template'] ?? null;
      if (null !== $xml) {
        file_put_contents($xmlPath, $xml);
      }
    }
  }
}

new Script();
