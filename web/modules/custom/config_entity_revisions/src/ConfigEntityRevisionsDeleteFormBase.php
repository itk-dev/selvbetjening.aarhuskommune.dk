<?php

namespace Drupal\config_entity_revisions;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Router;

/**
 * Provides a form for reverting a config entity revision.
 */
abstract class ConfigEntityRevisionsDeleteFormBase extends ConfirmFormBase implements ContainerInjectionInterface {

  use MessengerTrait;

  /**
   * The config entity.
   *
   * @var ConfigEntityRevisionsInterface
   */
  protected $configEntity;

  /**
   * The revision to be deleted.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $revision;

  /**
   * Constructs a new ConfigEntityRevisionsRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $configEntityRevisionsStorage
   *   The ConfigEntityRevisions storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Datetime\DateFormatter $dateFormatter
   *   The date formatter service.
   * @param \Drupal\config_entity_revisions\ConfigEntityRevisionsControllerInterface $controller
   *   The controller interface.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   The module handler service.
   * @param \Symfony\Component\Routing\Router $router
   *   The router service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    protected EntityStorageInterface $configEntityRevisionsStorage,
    protected Connection $connection,
    protected DateFormatterInterface $dateFormatter,
    protected ConfigEntityRevisionsControllerInterface $controller,
    protected ModuleHandler $moduleHandler,
    Router $router,
    RequestStack $requestStack,
  ) {
    $match = $router->matchRequest($requestStack->getCurrentRequest());
    $this->configEntity = $match['config_entity']->revisionedEntity();

    $this->revision = $this->configEntityRevisionsStorage->loadRevision($match['revision_id']);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->configEntity->moduleName() . '_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url("entity." . $this->configEntity->getEntityTypeId() . ".revisions", [
      $this->configEntity->getEntityTypeId() => $this->configEntity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    if (!$this->configEntity->hasOwnContent()) {
      return '';
    }

    $count = $this->configEntity->contentEntityCount($this->revision->value);
    if ($count) {
      return $this->stringTranslation
        ->formatPlural($count, '1 submission will be deleted.', '@count submissions will be deleted.');
    }

    return '';
  }

  /**
   * Redirect the user back to the revisions overview page.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state to be modified.
   */
  public function setRevisionsFormRedirect(FormStateInterface &$form_state) {
    $form_state->setRedirect(
      'entity.' . $this->configEntity->configEntityName() . '.revisions',
      [$this->configEntity->configEntityName() => $this->configEntity->id()]
    );
  }

  /**
   * Delete the current revision.
   */
  public function performDeletion() {
    $this->controller->deleteRevision($this->revision);
  }

  /**
   * Log a revision deletion.
   */
  public function logUpdate() {
    $this->logger('content')
      ->notice('Deleted %label revision %revision.', [
        '%label' => $this->configEntity->label(),
        '%revision' => $this->revision->getRevisionId(),
      ]);
  }

  /**
   * Display a message to the user.
   */
  public function displayUpdate() {
    $message = new TranslatableMarkup('Revision from %revision-date of %form has been deleted.', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
      '%form' => $this->configEntity->label(),
    ]);
    $this->messenger()->addWarning($message);
  }

  /**
   * Redirect the user back to the config entity build page.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state to be modified.
   */
  public function setBuildFormRedirect(FormStateInterface &$form_state) {
    $form_state->setRedirect(
      'entity.' . $this->configEntity->configEntityName() . '.edit_form',
      [$this->configEntity->configEntityName() => $this->configEntity->id()]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Someone else may have deleted a revision so we can't assume the state
    // when the form was rendered is still valid.
    $revisions = $this->controller->getRevisionIds($this->configEntity->getContentEntityId());
    if (count($revisions) == 1) {
      $this->messenger()->addError('There is only one revision remaining. You may not delete it, sorry.');
    }
    else {
      $this->controller->deleteRevision($this->configEntity->getRevisionId());
      $this->logUpdate();
      $this->displayUpdate();
    }

    if (count($revisions) > 2) {
      $this->setRevisionsFormRedirect($form_state);
    }
    else {
      $this->setBuildFormRedirect($form_state);
    }
  }

}
