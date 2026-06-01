<?php

declare(strict_types=1);

namespace Drupal\selvbetjening_kp_forms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;


/**
 * Returns responses for selvbetjening_kp_forms routes.
 */
final class SelvbetjeningKpFormsController extends ControllerBase {
use StringTranslationTrait;

  /**
   * Builds the response.
   */
  public function __invoke(): array
  {
    $config = Settings::get('selvbetjening_kp_forms') ?? [];

    $build = [];

//    https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21Table.php/class/Table/10

    $colspan = 2 + count($config['handlers']);
    $header = [''];
    foreach ($config['sites'] as $name => $url) {
      $header[] = [
       'data' =>  $name,
        'colspan' => $colspan,
      ];
    }

    $rows = [];

    foreach ($config['webforms'] as $label => $webformId) {
      $row = [$label];
      foreach ($config['sites'] as $name => $url) {
        $row[] = Link::fromTextAndUrl(
          $this->t('Elements source', ['@label' => $label]),
          $this->createUrl($url, 'entity.webform.source_form', ['webform' => $webformId]),
        );

        foreach ($config['handlers'] as $handlerLabel => $handlerId) {
          $row[] = Link::fromTextAndUrl(
            $this->t('Edit @label handler', ['@label' => $handlerLabel]),
            $this->createUrl($url, 'entity.webform.handler.edit_form', ['webform' => $webformId, 'webform_handler' => $handlerId]),
          );
        }

        $row[] = Link::fromTextAndUrl(
          $this->t('Handlers', ['@label' => $label]),
         $this->createUrl($url, 'entity.webform.handlers', ['webform' => $webformId]),
        );
     }

      $rows[] = $row;
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return $build;
  }

  private function createUrl(string $baseUri, ?string $name = null, ?array $parameters = []): Url
  {
    if (null === $name) {
      return Url::fromUri($baseUri);
    }

    $path = Url::fromRoute($name, $parameters);

    return Url::fromUri($baseUri . $path->toString(true)->getGeneratedUrl());
  }

}
