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
  public function __invoke(): array {
    $config = Settings::get('selvbetjening_kp_forms') ?? [];
    $sites = (array) ($config['sites'] ?? NULL);
    $webforms = (array) ($config['webforms'] ?? NULL);
    $handlers = (array) ($config['handlers'] ?? NULL);

    $build = [];

    // https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21Table.php/class/Table/10
    $colspan = 2 + count($handlers);
    $header = [''];
    foreach ($sites as $name => $url) {
      $header[] = [
        'data' => $name,
        'colspan' => $colspan,
      ];
    }

    $rows = [];

    foreach ($webforms as $label => $webformId) {
      $row = [sprintf('%s (%s)', $label, $webformId)];
      foreach ($sites as $name => $url) {
        $row[] = Link::fromTextAndUrl(
          $this->t('Elements source', ['@label' => $label]),
          $this->createUrl($url, 'entity.webform.source_form', ['webform' => $webformId]),
        );

        foreach ($handlers as $handlerLabel => $handlerId) {
          $row[] = Link::fromTextAndUrl(
            $this->t('Edit @label handler', ['@label' => $handlerLabel]),
            $this->createUrl($url, 'entity.webform.handler.edit_form', [
              'webform' => $webformId,
              'webform_handler' => $handlerId,
            ]),
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

  /**
   * Create URL.
   */
  private function createUrl(string $baseUri, ?string $name = NULL, ?array $parameters = []): Url {
    if (NULL === $name) {
      return Url::fromUri($baseUri);
    }

    $path = Url::fromRoute($name, $parameters);

    return Url::fromUri($baseUri . $path->toString(TRUE)->getGeneratedUrl());
  }

}
