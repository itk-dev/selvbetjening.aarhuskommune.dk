<?php

namespace Drupal\os2forms_selvbetjening_deprecations\Form;

use Drupal\Core\Database\Connection;
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
    private readonly EntityStorageInterface $userStorage,
    private readonly Connection $connection,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity_type.manager')->getStorage('webform'),
      $container->get('entity_type.manager')->getStorage('taxonomy_term'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get(Connection::class),
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

    // Gather webforms using the taxonomy terms.
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

    // Gather user assigned to the taxonomy terms.
    $users = $this->userStorage->loadMultiple();
    foreach ($users as $user) {
      $name = $user->getDisplayName();
      $userId = $user->id();

      $term_ids = $this->connection->select('permissions_by_term_user', 'ptu')
        ->fields('ptu', ['tid'])
        ->condition('ptu.uid', $userId)
        ->execute()
        ->fetchCol();

      foreach ($term_ids as $term_id) {
        if ($taxonomies[$term_id]) {
          $taxonomies[$term_id]['users'][] = $name;
        }
      }
    }

    $form['taxonomies']['description'] = [
      '#markup' => sprintf('<div><strong>Total number of user affiliation taxonomies: %d </strong></div>', count($taxonomies)),
    ];

    foreach ($taxonomies as $key => $value) {
      $forms = $value['webforms'];

      $form['taxonomies'][$key] = [
        '#type' => 'details',
        '#title' => $value['name'],
        '#open' => FALSE,
      ];

      $form['taxonomies'][$key]['webforms'] = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#title' => 'Webforms: ' . count($forms),
        '#items' => $forms
          ? array_map(
            static fn(string $webformId) => Link::createFromRoute($webformId, 'entity.webform.handlers', ['webform' => $webformId]),
            $forms
        )
          : [$this->t('No forms')],
      ];

      $users = $value['users'] ?? [];
      uasort($users, fn($a, $b) => $a <=> $b);

      $form['taxonomies'][$key]['users'] = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#title' => 'Users: ' . count($users),
        '#items' => !empty($users) ? $users : [$this->t('No users')],
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
