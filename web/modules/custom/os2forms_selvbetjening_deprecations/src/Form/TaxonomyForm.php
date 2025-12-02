<?php

namespace Drupal\os2forms_selvbetjening_deprecations\Form;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\WebformEntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Taxonomy form.
 */
final class TaxonomyForm extends FormBase {
  use StringTranslationTrait;

  /**
   * Constructor.
   */
  public function __construct(
    private readonly WebformEntityStorageInterface $webformEntityStorage,
    private readonly EntityStorageInterface $taxonomyTermStorage,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity_type.manager')->getStorage('webform'),
      $container->get('entity_type.manager')->getStorage('taxonomy_term'),
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

    $vocabularies = $this->taxonomyTermStorage->loadByProperties(['vid' => 'user_affiliation']);

    $taxonomies = [];
    foreach ($vocabularies as $term) {
      /** @var \Drupal\taxonomy\Entity\Term $term */
      $taxonomies[$term->id()] = [
        'name' => $term->getName(),
        'webforms' => [],
      ];
    }

    uasort($taxonomies, fn($a, $b) => $a['name'] <=> $b['name']);

    foreach ($this->webformEntityStorage->getWebformIds() as $webformId) {
      $webform = $this->webformEntityStorage->load($webformId);
      $os2formsPermissionsByTermSettings = $webform->getThirdPartySettings('os2forms_permissions_by_term');
      if (array_key_exists('settings', $os2formsPermissionsByTermSettings)) {
        foreach ($os2formsPermissionsByTermSettings['settings'] as $permission) {
          if ($permission) {
            if (array_key_exists($permission, $taxonomies)) {
              $taxonomies[$permission]['webforms'][] = $webformId;
            }
          }
        }
      }
    }

    $form['taxonomies']['description'] = [
      '#markup' => sprintf('<div><strong>Total number of user affiliation taxonomies: %d </strong></div>', count($taxonomies)),
    ];

    foreach ($taxonomies as $key => $value) {
      $forms = $value['webforms'];
      $form['taxonomies'][$key] = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#title' => $value['name'] . ': ' . count($forms),
        '#items' => $forms
          ? array_map(
            static fn(string $webformId) => Link::createFromRoute($webformId, 'entity.webform.handlers', ['webform' => $webformId]),
            $forms
        )
          : [$this->t('No forms')],
      ];
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

}
