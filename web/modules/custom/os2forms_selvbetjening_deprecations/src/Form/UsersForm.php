<?php

namespace Drupal\os2forms_selvbetjening_deprecations\Form;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Drupal\webform\WebformEntityStorageInterface;
use Drupal\webform\WebformInterface;
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

    $users = $this->userStorage->loadMultiple();
    unset($users[0]);

    $userOptions = [];
    foreach ($users as $user) {
      /** @var \Drupal\user\UserInterface $user */
      if ($user->isAnonymous()) {
        continue;
      }
      $userOptions[$user->id()] = $this->getUserLabel($user);
    }
    // Keep non-capital (api and placeholder) users at the bottom.
    asort($userOptions, SORT_NATURAL);

    $selectedUsers = $this->getRequest()->get('users');
    if (!is_array($selectedUsers)) {
      $selectedUsers = [];
    }
    $selectedUsers = array_filter($selectedUsers);

    $showNoOwner = (bool) $this->getRequest()->get('show_no_owner');

    $form['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Filters'),
      '#description' => $this->t('Shows webforms and nodes authored by the selected users'),
      '#open' => empty($selectedUsers) && !$showNoOwner,
    ];

    $form['filters']['users'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Users'),
      '#options' => $userOptions,
      '#default_value' => $selectedUsers,
      '#multiple' => TRUE,
    ];

    $form['filters']['show_no_owner'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show webforms with no owner'),
      '#default_value' => $showNoOwner,
    ];

    $form['filters']['actions']['#type'] = 'actions';

    $form['filters']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Show users'),
      '#attributes' => ['name' => ''],
    ];

    if (!empty($selectedUsers) || $showNoOwner) {
      $form['users'] = [
        '#type' => 'container',
        '#weight' => 9999,
      ];

      $form['users']['refresh'] = Link::fromTextAndUrl(
          $this->t('Refresh'),
          Url::fromRoute('<current>', $this->getRequest()->query->all())
        )->toRenderable()
        + [
          '#attributes' => [
            'class' => ['button', 'btn'],
          ],
        ];

      $webformsByOwner = $this->groupWebformsByOwner();
      $nodesByOwner = !empty($selectedUsers)
        ? $this->loadAuthoredWebformNodes($selectedUsers)
        : [];

      $orphanedWebforms = $showNoOwner ? ($webformsByOwner[self::OWNER_NONE] ?? []) : [];
      if (!empty($orphanedWebforms)) {
        $form['users']['no_owner'] = [
          '#type' => 'details',
          '#title' => $this->t('Webforms with no owner (@count)', ['@count' => count($orphanedWebforms)]),
          '#open' => FALSE,
        ];
        $form['users']['no_owner']['webforms'] = [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          '#items' => array_map(
            static fn(WebformInterface $webform) => Link::createFromRoute(
              sprintf('%s (%s)', $webform->label(), $webform->id()),
              'entity.webform.edit_form',
              ['webform' => $webform->id()],
            ),
            $orphanedWebforms,
          ),
        ];
      }

      foreach ($selectedUsers as $uid) {
        $user = $this->userStorage->load($uid);
        if (!$user instanceof UserInterface) {
          continue;
        }

        $ownedWebforms = $webformsByOwner[$uid] ?? [];
        $ownedNodes = $nodesByOwner[$uid] ?? [];

        $form['users'][$uid] = [
          '#type' => 'details',
          '#title' => $this->getUserLabel($user),
          '#open' => count($selectedUsers) === 1,
        ];

        $form['users'][$uid]['webforms'] = [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          '#title' => $this->t('Webforms: @count', ['@count' => count($ownedWebforms)]),
          '#items' => $ownedWebforms
            ? array_map(
              static fn(WebformInterface $webform) => Link::createFromRoute(
                sprintf('%s (%s)', $webform->label(), $webform->id()),
                'entity.webform.edit_form',
                ['webform' => $webform->id()],
              ),
              $ownedWebforms,
          )
            : [$this->t('No webforms')],
        ];

        $form['users'][$uid]['nodes'] = [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          '#title' => $this->t('Nodes: @count', ['@count' => count($ownedNodes)]),
          '#items' => $ownedNodes
            ? array_map(
              static fn(NodeInterface $node) => Link::createFromRoute(
                sprintf('%s (%d)', $node->label(), (int) $node->id()),
                'entity.node.edit_form',
                ['node' => $node->id()],
              ),
              $ownedNodes,
          )
            : [$this->t('No nodes')],
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
   * Group all webforms by owner uid.
   *
   * @return array<int|string, \Drupal\webform\WebformInterface[]>
   *   Webforms grouped by owner id.
   */
  private function groupWebformsByOwner(): array {
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
   * @param array<int|string, int|string> $uids
   *   Selected user ids.
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
    $mail = $user->getEmail() ?? '';
    return sprintf('%s (%s)', $user->getDisplayName(), $mail);
  }

}
