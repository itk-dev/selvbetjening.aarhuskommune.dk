<?php

namespace Drupal\os2forms_selvbetjening_deprecations\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\webform\WebformEntityStorageInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Webforms form.
 */
final class WebformsForm extends FormBase {
  use StringTranslationTrait;

  /**
   * Constructor.
   */
  public function __construct(
    private readonly WebformEntityStorageInterface $webformEntityStorage,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity_type.manager')->getStorage('webform')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2forms_selvbetjening_deprecations_webforms';
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   * @phpstan-return array<string, mixed>
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_state->setMethod(Request::METHOD_GET);

    $webformsOptions = array_map(
      $this->getWebformLabel(...),
      $this->webformEntityStorage->loadMultiple()
    );

    $selectedWebforms = $this->getRequest()->get('webforms');
    if (!is_array($selectedWebforms)) {
      $selectedWebforms = [];
    }

    $form['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Filters'),
      '#description' => $this->t('Shows handlers and elements of selected webforms'),
      '#open' => empty($selectedWebforms),
    ];

    $form['filters']['webforms'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Webforms'),
      '#required' => TRUE,
      '#options' => $webformsOptions,
      '#default_value' => $selectedWebforms,
      '#multiple' => TRUE,
    ];

    $form['filters']['actions']['#type'] = 'actions';

    $form['filters']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Show webforms'),
      '#attributes' => ['name' => ''],
    ];

    if (!empty($selectedWebforms)) {

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

      foreach ($selectedWebforms as $webform) {
        /** @var \Drupal\webform\WebformInterface $webform */
        $webform = $this->webformEntityStorage->load($webform);
        $handlers = $webform->getHandlers()->getConfiguration();
        $handlersFiltered = array_unique(array_column($handlers, 'id'));
        $elements = $webform->getElementsDecodedAndFlattened();
        $elementsFiltered = array_unique(array_column($elements, '#type'));

        $form['webforms'][$webform->id()] = [
          '#type' => 'details',
          '#title' => $webform->label() . ' (' . $webform->id() . ')',
          '#description' => $this->t('Note: Elements and handler types being used multiple times will only occur once.'),
          '#open' => count($selectedWebforms) === 1,
        ];

        $form['webforms'][$webform->id()]['handlers'] = [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          '#title' => Link::createFromRoute($this->t('Handlers: @count', ['@count' => count($handlersFiltered)]), 'entity.webform.handlers', ['webform' => $webform->id()]),
          '#items' => $handlersFiltered ?: [$this->t('No handlers')],
        ];

        $form['webforms'][$webform->id()]['elements'] = [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          '#title' => Link::createFromRoute($this->t('Elements: @count', ['@count' => count($elementsFiltered)]), 'entity.webform.edit_form', ['webform' => $webform->id()]),
          '#items' => $elementsFiltered ?: [$this->t('No elements')],

        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  public function submitForm(array &$form, FormStateInterface $formState): void {
  }

  /**
   * Get webform label.
   */
  private function getWebformLabel(WebformInterface $webform): string {
    return sprintf('%s (%s)', $webform->label(), $webform->id());
  }

}
