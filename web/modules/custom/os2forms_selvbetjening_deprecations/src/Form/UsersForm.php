<?php

namespace Drupal\os2forms_selvbetjening_deprecations\Form;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Drupal\webform\WebformEntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Users form.
 */
final class UsersForm extends FormBase {
  use StringTranslationTrait;

  private const string OWNER_NONE = '__none__';

  /**
   * Constructor.
   */
  public function __construct(
    private readonly WebformEntityStorageInterface $webformEntityStorage,
    private readonly EntityStorageInterface $userStorage,
    private readonly EntityStorageInterface $nodeStorage,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity_type.manager')->getStorage('webform'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('entity_type.manager')->getStorage('node'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2forms_selvbetjening_deprecations_users';
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   * @phpstan-return array<string, mixed>
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_state->setMethod(Request::METHOD_GET);

    /** @var \Drupal\user\UserInterface[] $users */
    $users = $this->userStorage->loadMultiple();

    $users = array_filter(
      $users,
      static fn (UserInterface $user) => !$user->isAnonymous()
    );
    $userOptions = array_map($this->getUserLabel(...), $users);

    // Keep non-capital (api and placeholder) users at the bottom.
    asort($userOptions, SORT_NATURAL);

    $query = $this->getRequest()->query;
    $selectedUsers = $query->all('users') ?? [];
    $showOrphanedWebforms = (bool) $query->get('show_orphaned_webforms');

    $form['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Filters'),
      '#description' => $this->t('Shows webforms and nodes authored by the selected users'),
      '#open' => empty($selectedUsers) && !$showOrphanedWebforms,
    ];

    $form['filters']['users'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Users'),
      '#options' => $userOptions,
      '#default_value' => $selectedUsers,
      '#multiple' => TRUE,
    ];

    $form['filters']['show_orphaned_webforms'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show webforms with no owner'),
      '#default_value' => $showOrphanedWebforms,
    ];

    $form['filters']['actions']['#type'] = 'actions';

    $form['filters']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Show users'),
      '#attributes' => ['name' => ''],
    ];

    if (!empty($selectedUsers) || $showOrphanedWebforms) {
      $form['users'] = [
        '#type' => 'container',
        '#weight' => 9999,
      ];

      $form['users']['refresh'] = Link::fromTextAndUrl(
          $this->t('Refresh'),
          Url::fromRoute('<current>', $query->all())
        )->toRenderable()
        + [
          '#attributes' => [
            'class' => ['button', 'btn'],
          ],
        ];

      $webformsByOwner = $this->loadWebformsGroupedByOwner();
      $nodesByOwner = !empty($selectedUsers)
        ? $this->loadAuthoredWebformNodes($selectedUsers)
        : [];

      $orphanedWebforms = $showOrphanedWebforms ? ($webformsByOwner[self::OWNER_NONE] ?? []) : [];
      if (!empty($orphanedWebforms)) {
        $form['users']['no_owner'] = [
          '#type' => 'details',
          '#title' => $this->formatPlural(
            count($orphanedWebforms),
            'Webform with no owner',
            'Webforms with no owner (@count)',
            ['@count' => count($orphanedWebforms)]
          ),
          '#open' => FALSE,
        ];
        $form['users']['no_owner']['webforms'] = $this->formatEntityTable($orphanedWebforms, $this->t('Webform ID'));
      }

      $users = $this->userStorage->loadMultiple($selectedUsers);
      foreach ($users as $user) {
        $uid = $user->id();

        $ownedWebforms = $webformsByOwner[$uid] ?? [];
        $ownedNodes = $nodesByOwner[$uid] ?? [];

        $form['users'][$uid] = [
          '#type' => 'details',
          '#title' => $this->getUserLabel($user),
          '#open' => count($selectedUsers) === 1,
        ];

        $form['users'][$uid]['webforms_heading'] = $this->formatCountHeading(
          $this->t('Webforms: @count', ['@count' => count($ownedWebforms)])
        );
        $form['users'][$uid]['webforms_table'] = $this->formatEntityTable($ownedWebforms, $this->t('Webform ID'));

        $form['users'][$uid]['nodes_heading'] = $this->formatCountHeading(
          $this->t('Nodes: @count', ['@count' => count($ownedNodes)])
        );
        $form['users'][$uid]['nodes_table'] = $this->formatEntityTable($ownedNodes, $this->t('Node ID'));
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
   * Load all webforms by owner uid.
   *
   * @return array<int|string, \Drupal\webform\WebformInterface[]>
   *   Webforms grouped by owner id.
   */
  private function loadWebformsGroupedByOwner(): array {
    $grouped = [];
    foreach ($this->webformEntityStorage->loadMultiple() as $webform) {
      /** @var \Drupal\webform\WebformInterface $webform */
      $ownerId = $webform->getOwnerId();
      $key = $ownerId ?? self::OWNER_NONE;
      $grouped[$key][] = $webform;
    }
    return $grouped;
  }

  /**
   * Load webform-type nodes authored by any of the given users.
   *
   * @param array<int, string> $uids
   *   User ids.
   *
   * @return array<int|string, \Drupal\node\NodeInterface[]>
   *   Nodes grouped by owner uid.
   */
  private function loadAuthoredWebformNodes(array $uids): array {
    $nids = $this->nodeStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'webform')
      ->condition('uid', array_values($uids), 'IN')
      ->execute();

    if (empty($nids)) {
      return [];
    }

    $grouped = [];
    foreach ($this->nodeStorage->loadMultiple($nids) as $node) {
      /** @var \Drupal\node\NodeInterface $node */
      $grouped[$node->getOwnerId()][] = $node;
    }
    return $grouped;
  }

  /**
   * Get user label.
   */
  private function getUserLabel(UserInterface $user): string {
    $name = $user->getDisplayName();
    $email = $user->getEmail();

    return $email ? "$name ($email)" : $name;
  }

  /**
   * Build a table render array listing entities with a link to their edit form.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   Entities of a single type (webform or node).
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $idLabel
   *   Header label for the id column.
   */
  private function formatEntityTable(array $entities, TranslatableMarkup $idLabel): array {
    $rows = [];
    foreach ($entities as $entity) {
      $entityTypeId = $entity->getEntityTypeId();
      $rows[] = [
        'id' => $entity->id(),
        'name' => [
          'data' => [
            '#type' => 'link',
            '#title' => $entity->label(),
            '#url' => Url::fromRoute(
              "entity.$entityTypeId.edit_form",
              [$entityTypeId => $entity->id()],
            ),
          ],
        ],
      ];
    }

    return [
      '#type' => 'table',
      '#header' => [
        'id' => $idLabel,
        'name' => $this->t('Name'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No entries available.'),
    ];
  }

  /**
   * Build a h3 heading render array.
   */
  private function formatCountHeading(TranslatableMarkup $label): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $label,
    ];
  }

}
