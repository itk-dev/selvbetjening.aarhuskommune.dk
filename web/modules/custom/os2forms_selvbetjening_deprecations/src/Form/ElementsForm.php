<?php

namespace Drupal\os2forms_selvbetjening_deprecations\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformEntityStorageInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Elements form.
 */
final class ElementsForm extends FormBase {
  use StringTranslationTrait;

  /**
   * Constructor.
   */
  public function __construct(
    private readonly WebformElementManagerInterface $webformElementManager,
    private readonly WebformEntityStorageInterface $webformEntityStorage,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('plugin.manager.webform.element'),
      $container->get('entity_type.manager')->getStorage('webform')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2forms_selvbetjening_deprecations_elements';
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

    $elementOptions = array_map(
      $this->getWebformElementLabel(...),
      $this->getWebformElementDefinitions()
    );

    $selectedElements = $this->getRequest()->get('elements');
    if (!is_array($selectedElements)) {
      $selectedElements = [];
    }

    $form['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Filters'),
    ];

    $form['filters']['elements'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Elements'),
      '#required' => TRUE,
      '#options' => $elementOptions,
      '#default_value' => $selectedElements,
      '#multiple' => TRUE,
    ];

    $form['filters']['actions']['#type'] = 'actions';

    $form['filters']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Show webforms with selected elements'),
      '#attributes' => ['name' => ''],
    ];

    if (!empty($selectedElements)) {
      foreach ($selectedElements as $type) {
        $webforms[$type] = array_filter(
          $this->webformEntityStorage->loadMultiple(),
          static fn(WebformInterface $webform) => !empty(array_filter(
            $webform->getElementsDecodedAndFlattened(),
            fn(array $element) => $type === ($element['#type'] ?? NULL))
          )
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

      foreach ($webforms as $elementType => $forms) {
        $element = $this->getWebformElementDefinitions()[$elementType];
        $form['webforms'][$elementType] = [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          '#title' => $this->getWebformElementLabel($element) . ': ' . count($forms),
          '#items' => $forms
            ? array_map(
              static fn(WebformInterface $webform) => Link::fromTextAndUrl($webform->label(), $webform->toUrl('edit-form')),
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
   * Webform element definitions.
   *
   * @var array|null
   */
  private ?array $webformElementDefinitions = NULL;

  /**
   * Get webform element definitions.
   *
   * @return array[]
   *   The definitions.
   */
  private function getWebformElementDefinitions(): array {
    if (NULL === $this->webformElementDefinitions) {
      $this->webformElementDefinitions = $this->webformElementManager->getSortedDefinitions();
      uasort($this->webformElementDefinitions, fn (array $a, array $b) => $this->isExcluded($a) <=> $this->isExcluded($b));
    }

    return $this->webformElementDefinitions;
  }

  /**
   * Decide if a webform element is excluded.
   *
   * @see WebformElementManagerInterface::isExcluded()
   */
  private function isExcluded(array $definition): bool {
    return $this->webformElementManager->isExcluded($definition['id']);
  }

  /**
   * Get webform element label.
   */
  private function getWebformElementLabel(array $definition): string {
    return sprintf('%s %s (%s)', $this->isExcluded($definition) ? '[excluded]' : '', $definition['label'], $definition['id']);
  }

}
