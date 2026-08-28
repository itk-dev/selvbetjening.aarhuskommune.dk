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
      $id = $webform->id();
      $handler = $webform->getHandler($this->handlerId);

      $path = 'settings.distribution_object.xml_template';
      $xmlPath = __DIR__ . '/../handlers/webform.webform.' . $webform->id() . '.handlers.' . $handler->getHandlerId() . '.' . $path . '.xml.twig';

      $this->writeln($webform->label());
      $this->writeln($path);

      $configuration = $handler->getConfiguration();

      $xml = file_get_contents($xmlPath);
      NestedArray::setValue($configuration, explode('.', $path), $xml);
      $handler->setConfiguration($configuration);
      $webform->save();
    }
  }
}

new Script();
