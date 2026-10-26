<?php

namespace Drupal\os2forms_selvbetjening\Plugin\views\field;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\ResettableStackedRouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\author_bulk_assignment\Plugin\views\field\AuthorAssignmentEntityBulkForm;
use Drupal\node\NodeInterface;
use Drupal\permissions_by_term\Service\AccessStorage;
use Drupal\user\Entity\User;
use Drupal\webform\Entity\Webform;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a user operations bulk form element with term-based restrictions.
 *
 * @ViewsField("author_assignment_node_bulk_form_override")
 */
class AuthorAssignmentNodeBulkFormOverride extends AuthorAssignmentEntityBulkForm {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current language code.
   *
   * @var string
   */
  protected string $langcode;

  /**
   * Constructs a new AuthorAssignmentNodeBulkFormOverride instance.
   */
  public function __construct(
    private readonly AccessStorage $accessStorage,
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager,
    LanguageManagerInterface $language_manager,
    MessengerInterface $messenger,
    EntityRepositoryInterface $entity_repository,
    private readonly AccountProxyInterface $currentUser,
    #[Autowire(service: 'current_route_match')]
    ResettableStackedRouteMatchInterface $route_match,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entityTypeManager, $language_manager, $messenger, $entity_repository, $route_match);
    $this->langcode = $language_manager->getCurrentLanguage()->getId();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    /** @var static */
    return new self(
      $container->get('permissions_by_term.access_storage'),
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('messenger'),
      $container->get('entity.repository'),
      $container->get('current_user'),
      $container->get('current_route_match'),
    );
  }

  /**
   * Gets the taxonomy terms that the current user has access to.
   *
   * @return array
   *   An array of term IDs that the current user has permission to access.
   *   Returns an empty array if no terms are accessible or if the user
   *   has no permissions.
   */
  private function getUserTerms(): array {
    $currentUser = User::load($this->currentUser->id());

    return $this->accessStorage->getPermittedTids($currentUser->id(), $currentUser->getRoles());
  }

  /**
   * Retrieves a list of users based on term IDs.
   *
   * @param array $userTermsIds
   *   An array of term IDs for which to retrieve the associated users.
   *
   * @return array
   *   An associative array where the keys are user IDs and the values are
   *   the display names of the users. Returns an empty array if no users
   *   are found.
   */
  private function getUsersByTermId(array $userTermsIds): array {
    $users = [];
    if (!empty($userTermsIds)) {
      // Get all users that have access to these terms.
      foreach ($userTermsIds as $termId) {
        $userIdsResult = $this->accessStorage->getAllowedUserIds($termId, $this->langcode);
        foreach ($userIdsResult as $userId) {
          if (!isset($users[$userId])) {
            if ($user = User::load($userId)) {
              $users[$userId] = $user->getDisplayName();
            }
          }
        }
      }
    }
    return $users;
  }

  /**
   * Filters users by their access to terms.
   *
   * @param array $users
   *   An associative array of users where the key is the user ID and
   *   the value is the username.
   * @param array $termArrays
   *   An array of term arrays to check access against.
   *
   * @return array
   *   A filtered list of users who have access to the given terms.
   */
  private function filterUsersByTermArray(array $users, array $termArrays): array {
    $mergedTerms = array_values($termArrays);
    $mergedTerms = !empty($mergedTerms) ? (is_array($mergedTerms[0]) ? array_merge(...$mergedTerms) : $mergedTerms) : [];

    return array_filter($users, function ($userName, $userId) use ($mergedTerms) {
      $user = User::load($userId);
      $userTermsIds = $this->accessStorage->getPermittedTids($user->id(), $user->getRoles());

      // Check if user has access to at least one of the terms.
      return empty($mergedTerms) || !empty(array_intersect($userTermsIds, $mergedTerms));
    }, ARRAY_FILTER_USE_BOTH);
  }

  /**
   * {@inheritdoc}
   */
  public function viewsForm(&$form, FormStateInterface $form_state): void {
    parent::viewsForm($form, $form_state);

    $form['header']['node_bulk_form']['assignee_uid']['#type'] = 'select';
    $form['header']['node_bulk_form']['assignee_uid']['#chosen'] = TRUE;
    $form['header']['node_bulk_form']['assignee_uid']['#attributes']['class'][] = 'chosen-container-bulk';
    $form['#attached']['library'][] = 'os2forms_selvbetjening/author_bulk_assignment';

    $form['header']['node_bulk_form']['action']['#options']['node_author_bulk_assignment_action'] = $this->t('Change ownership');

    $userTermsIds = $this->getUserTerms();
    $users = $this->getUsersByTermId($userTermsIds);
    asort($users);

    $form_state->set('users', $users);

    $form['header']['node_bulk_form']['assignee_uid']['#options'] = $users;
  }

  /**
   * Validates the bulk form submission for assigning nodes.
   *
   * @param array &$form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsFormValidate(&$form, FormStateInterface $form_state): void {
    parent::viewsFormValidate($form, $form_state);

    $users = $form_state->get('users');
    $user_input = $form_state->getUserInput();
    $selected = array_filter($user_input[$this->options['id']]);
    $nodePermissionsByTermArray = [];
    $webformPermissionsByTermArray = [];

    // Arrays to store inaccessible items.
    $inaccessibleNodes = [];
    $inaccessibleWebforms = [];

    // Loop selected nodes and get entities.
    foreach ($selected as $bulk_form_key) {
      $webform = NULL;
      $entity = $this->loadEntityFromBulkFormKey($bulk_form_key);

      // If node entity exists, get permissions and add to array.
      if ($entity instanceof NodeInterface && $entity->hasField('webform')) {
        $nodePermissionsByTerm = $entity->hasField('field_os2forms_permissions') ? array_column($entity->get('field_os2forms_permissions')
          ->getValue(), 'target_id') : [];
        $nodePermissionsByTermArray = $this->addUniquePermissions($nodePermissionsByTermArray, $nodePermissionsByTerm);
        $webform = $entity->get('webform')->entity;
      }

      // If webform exists, get permissions and add to array.
      if ($webform instanceof Webform) {
        $webformPermissionsByTerm = $webform->getThirdPartySetting('os2forms_permissions_by_term', 'settings');
        $webformPermissionsByTermArray = $this->addUniquePermissions(
          $webformPermissionsByTermArray,
          $webformPermissionsByTerm
        );
      }
      else {
        $form_state->setErrorByName('AuthorAssignmentNodeBulkFormError', $this->t('One or more of the selected nodes does not have a webform connected to it.'));
      }
    }

    $selectedAssignee = $form_state->getValue('assignee_uid');

    // Check node permissions.
    $filteredUsersByNodePermission = $this->filterUsersByTermArray($users, $nodePermissionsByTermArray);

    if (!isset($filteredUsersByNodePermission[$selectedAssignee]) && $selectedAssignee != 0) {
      // Collect inaccessible nodes.
      foreach ($selected as $bulk_form_key) {
        $entity = $this->loadEntityFromBulkFormKey($bulk_form_key);
        if ($entity instanceof NodeInterface) {
          $nodePermissionsByTerm = $entity->hasField('field_os2forms_permissions') ?
            array_column($entity->get('field_os2forms_permissions')->getValue(), 'target_id') : [];

          // Check if user has access to this specific node's terms.
          $userHasAccess = $this->filterUsersByTermArray([$selectedAssignee => ''], [$nodePermissionsByTerm]);
          if (empty($userHasAccess)) {
            $inaccessibleNodes[] = $entity->getTitle();
          }
        }
      }

      $nodesList = implode(', ', $inaccessibleNodes);
      $form_state->setErrorByName(
        'AuthorAssignmentNodeBulkFormError',
        $this->t('The selected user does not have access to the following nodes: @nodes', ['@nodes' => $nodesList])
      );
    }

    // Check webform permissions.
    $filteredUsersByWebformPermission = $this->filterUsersByTermArray($users, $webformPermissionsByTermArray);

    if (!isset($filteredUsersByWebformPermission[$selectedAssignee]) && $selectedAssignee != 0) {
      // Collect inaccessible webforms.
      foreach ($selected as $bulk_form_key) {
        $entity = $this->loadEntityFromBulkFormKey($bulk_form_key);
        if ($entity instanceof NodeInterface && $entity->hasField('webform')) {
          $webform = $entity->get('webform')->entity;
          if ($webform instanceof Webform) {
            $webformPermissionsByTerm = $webform->getThirdPartySetting('os2forms_permissions_by_term', 'settings');

            // Check if user has access to this specific webform's terms.
            $userHasAccess = $this->filterUsersByTermArray([$selectedAssignee => ''], [$webformPermissionsByTerm]);
            if (empty($userHasAccess)) {
              $inaccessibleWebforms[] = $webform->label();
            }
          }
        }
      }

      $webformsList = implode(', ', $inaccessibleWebforms);
      $form_state->setErrorByName(
        'AuthorAssignmentNodeBulkFormError',
        $this->t('The selected user does not have access to the following webforms: @webforms', ['@webforms' => $webformsList])
      );
    }
  }

  /**
   * Adds unique permissions into the existing permissions array.
   *
   * @param array $existingPermissions
   *   The current permissions array.
   * @param array $newPermissions
   *   Permissions to merge.
   *
   * @return array
   *   The merged and filtered permissions array
   */
  private function addUniquePermissions(array $existingPermissions, array $newPermissions): array {
    // Filter out any empty/null values.
    $newPermissions = array_filter($newPermissions);

    return array_values(array_unique(
      array_merge($existingPermissions, $newPermissions)
    ));
  }

}
