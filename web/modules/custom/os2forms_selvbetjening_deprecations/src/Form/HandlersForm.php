<?php

namespace Drupal\os2forms_selvbetjening_deprecations\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\webform\Plugin\WebformHandlerInterface;
use Drupal\webform\Plugin\WebformHandlerManagerInterface;
use Drupal\webform\WebformEntityStorageInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handlers form.
 */
final class HandlersForm extends FormBase {
  use StringTranslationTrait;

  /**
   * Constructor.
   */
  public function __construct(
    private readonly WebformHandlerManagerInterface $webformHandlerManager,
    private readonly WebformEntityStorageInterface $webformEntityStorage,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('plugin.manager.webform.handler'),
      $container->get('entity_type.manager')->getStorage('webform')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2forms_selvbetjening_deprecations_handlers';
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   * @phpstan-return array<string, mixed>
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_state->setMethod(Request::METHOD_GET);
    $form['#after_build'] = [$this->afterBuild(...)];

    $handlerOptions = array_map(
      $this->getWebformHandlerLabel(...),
      $this->getWebformHandlerDefinitions()
    );

    $selectedHandlers = $this->getRequest()->get('handlers');
    if (!is_array($selectedHandlers)) {
      $selectedHandlers = [];
    }

    $form['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Filters'),
    ];

    $form['filters']['handlers'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Handlers'),
      '#required' => TRUE,
      '#options' => $handlerOptions,
      '#default_value' => $selectedHandlers,
      '#multiple' => TRUE,
    ];

    $form['filters']['actions']['#type'] = 'actions';

    $form['filters']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Show webforms with selected handlers'),
      '#attributes' => ['name' => ''],
    ];

    if (!empty($selectedHandlers)) {
      foreach ($selectedHandlers as $id) {
        $webforms[$id] = array_filter(
          $this->webformEntityStorage->loadMultiple(),
          static fn(WebformInterface $webform) => !empty(array_filter(
            iterator_to_array($webform->getHandlers()->getIterator()),
            fn(WebformHandlerInterface $handler) => $id === $handler->getPluginId()
          ))
        );
      }

      $form['webforms'] = [
        '#type' => 'container',
        '#weight' => 9999,
      ];

      $form['webforms']['refresh'] = Link::fromTextAndUrl(
          $this->t('Refresh'),
          Url::fromRoute('<current>', $this->getRequest()->query->all())
        )->toRenderable()
        + [
          '#attributes' => [
            'class' => ['button', 'btn'],
          ],
        ];

      foreach ($webforms as $handlerType => $forms) {
        $handler = $this->getWebformHandlerDefinitions()[$handlerType];
        $form['webforms'][$handlerType] = [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          '#title' => $this->getWebformHandlerLabel($handler) . ': ' . count($forms),
          '#items' => $forms
            ? array_map(
              static fn(WebformInterface $webform) => Link::createFromRoute($webform->label(), 'entity.webform.handlers', ['webform' => $webform->id()]),
              $forms
          )
            : [$this->t('No forms')],
        ];
      }
    }

    return $form;
  }

  /**
   * Remove elements from being submitted as GET variables.
   */
  public function afterBuild(array $element, FormStateInterface $form_state) {
    unset($element['form_token'], $element['form_build_id'], $element['form_id']);

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  public function submitForm(array &$form, FormStateInterface $formState): void {
  }

  /**
   * Webform handler definitions.
   *
   * @var array|null
   */
  private ?array $webformHandlerDefinitions = NULL;

  /**
   * Get webform handler definitions.
   *
   * @return array[]
   *   The definitions.
   */
  private function getWebformHandlerDefinitions(): array {
    if (NULL === $this->webformHandlerDefinitions) {
      $this->webformHandlerDefinitions = $this->webformHandlerManager->getSortedDefinitions();
      uasort($this->webformHandlerDefinitions, fn (array $a, array $b) => $this->isExcluded($a) <=> $this->isExcluded($b));
    }

    return $this->webformHandlerDefinitions;
  }

  /**
   * Decide if a webform handler is excluded.
   */
  private function isExcluded(array $definition): bool {
    return empty($this->webformHandlerManager->removeExcludeDefinitions([$definition['id'] => $definition]));
  }

  /**
   * Get webform handler label.
   */
  private function getWebformHandlerLabel(array $definition): string {
    return sprintf('%s %s (%s)', $this->isExcluded($definition) ? '[excluded]' : '', $definition['label'], $definition['id']);
  }

}
