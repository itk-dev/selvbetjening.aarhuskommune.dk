<?php

namespace Drupal\webform_revisions\Controller;

use Drupal\config_entity_revisions\ConfigEntityRevisionsControllerBase;
use Drupal\config_entity_revisions\ConfigEntityRevisionsControllerInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform_revisions\WebformRevisionsConfigTrait;

/**
 * Controller to make library functions available to various consumers.
 */
class WebformRevisionsController extends ConfigEntityRevisionsControllerBase implements ConfigEntityRevisionsControllerInterface {

  use WebformRevisionsConfigTrait;

  /**
   * Generates a title for the revision.
   *
   * This function is needed because the $webform parameter needs to match
   * the route but the parent's parameter is named $configEntity.
   *
   * @inheritdoc
   */
  public function revisionShowTitle(ConfigEntityInterface $webform) {
    return '"' . $webform->get('title') . '" webform, revision ' . $webform->getRevisionId();
  }

  /**
   * Perform alterations before a webform submission form is rendered.
   *
   * This hook is identical to hook_form_alter() but allows the
   * hook_webform_submission_form_alter() function to be stored in a dedicated
   * include file and it also allows the Webform module to implement webform
   * alter logic on another module's behalf.
   *
   * @param array $form
   *   Nested array of form elements that comprise the webform.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. The arguments that
   *   \Drupal::formBuilder()->getForm() was originally called with are
   *   available in the array $form_state->getBuildInfo()['args'].
   * @param string $form_id
   *   String representing the webform's id.
   *
   * @see webform.honeypot.inc
   * @see hook_form_BASE_FORM_ID_alter()
   * @see hook_form_FORM_ID_alter()
   *
   * @ingroup form_api
   */
  public static function submissionFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
    $form['#entity_builders']['webform_revisions'] = '\Drupal\webform_revisions\Controller\WebformRevisionsController::entityBuilder';
  }

  /**
   * Implements hook_webform_submission_presave.
   */
  public static function submissionPresave(WebformSubmissionInterface $webform_submission) {
    $match = \Drupal::service('router')->matchRequest(\Drupal::request());
    $webform_id = $match['webform'];
    $config_entity = \Drupal::entityTypeManager()->getStorage('webform')
      ->load($webform_id);

    $revisionsEntity = $config_entity->contentEntityStorage()
      ->loadRevision($config_entity->getRevisionID());
  }

  /**
   * Updates the form language to reflect any change to the entity language.
   *
   * There are use cases for modules to act both before and after form language
   * being updated, thus the update is performed through an entity builder
   * callback, which allows to support both cases.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param \Drupal\webform\Entity\WebformSubmission $entity
   *   The entity updated with the submitted values.
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\Core\Entity\ContentEntityForm::form()
   */
  public static function entityBuilder($entity_type_id, WebformSubmission $entity, array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform_revisions\Entity\WebformRevisions $webform */
    $webform = $entity->getWebform();

    $entity->set('webform_revision', $webform->getRevisionID());
  }

}
