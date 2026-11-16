<?php

namespace Drupal\config_entity_revisions;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Trait for adding form items for revisionable entities.
 */
trait RevisionableEntityFormTrait {

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    parent::prepareEntity();

    // Hide the current revision log message in UI.
    if ($this->showRevisionUi() && !$this->entity->isNew() && $this->entity instanceof RevisionLogInterface) {
      $this->entity->setRevisionLogMessage(NULL);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function revisionableEntityFormEarly(array $form, FormStateInterface $form_state) {

    if ($this->showRevisionUi()) {
      // Advanced tab must be the first, because other fields rely on that.
      if (!isset($form['advanced'])) {
        $form['advanced'] = [
          '#type' => 'vertical_tabs',
          '#weight' => 99,
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function revisionableEntityFormLate(array $form, FormStateInterface $form_state) {

    if ($this->showRevisionUi()) {
      $this->addRevisionableFormFields($form);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function revisionableEntityBuildEntity(array $form, FormStateInterface $form_state, EntityInterface $entity) {

    // Save as a new revision if requested to do so.
    if ($this->showRevisionUi() && !$form_state->isValueEmpty('revision')) {
      $entity->setNewRevision();
      if ($entity instanceof RevisionLogInterface) {
        // If a new revision is created, save the current user as
        // revision author.
        $entity->setRevisionUserId($this->currentUser()->id());
        $entity->setRevisionCreationTime($this->time->getRequestTime());
      }
    }

    return $entity;
  }

  /**
   * Add revision form fields if the entity enabled the UI.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  protected function addRevisionableFormFields(array &$form) {
    /** @var EntityTypeInterface $entity_type */
    $entity_type = $this->entity->getEntityType();

    $new_revision_default = $this->getNewRevisionDefault();
    $revisionField = $this->entity->get($entity_type->getKey('revision'));
    $revisionFieldAccess =
      ($revisionField ? $revisionField->access('update') : TRUE);

    // Add a log field if the "Create new revision" option is checked, or if the
    // current user has the ability to check that option.
    $form['revision_information'] = [
      '#type' => 'details',
      '#title' => $this->t('Revision information'),
      // Open by default when "Create new revision" is checked.
      '#open' => $new_revision_default,
      '#group' => 'advanced',
      '#weight' => 20,
      '#access' => $new_revision_default || $revisionFieldAccess,
      '#optional' => TRUE,
      '#attributes' => [
        'class' => ['entity-content-form-revision-information'],
      ],
      '#attached' => [
        'library' => ['core/drupal.entity-form'],
      ],
    ];

    $form['revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create new revision'),
      '#default_value' => $new_revision_default,
      '#access' => !$this->entity->isNew() && $revisionFieldAccess,
      '#group' => 'revision_information',
    ];

    // Get log message field's key from definition.
    if ($entity_type instanceof ContentEntityInterface) {
      $log_message_field = $entity_type->getRevisionMetadataKey('revision_log_message');
    }
    else {
      $log_message_field = 'revision_information';
    }

    if ($log_message_field && isset($form[$log_message_field])) {
      $form[$log_message_field] += [
        '#group' => 'revision_information',
        '#states' => [
          'visible' => [
            ':input[name="revision"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }
  }

  /**
   * Returns the bundle entity of the entity, or NULL if there is none.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The bundle entity.
   */
  protected function getBundleEntity() {
    if ($bundle_entity_type = $this->entity->getEntityType()->getBundleEntityType()) {
      return $this->entityTypeManager->getStorage($bundle_entity_type)->load($this->entity->bundle());
    }
    return NULL;
  }

  /**
   * Should new revisions created on default.
   *
   * @return bool
   *   New revision on default.
   */
  protected function getNewRevisionDefault() {
    $new_revision_default = FALSE;
    $bundle_entity = $this->getBundleEntity();
    if ($bundle_entity instanceof RevisionableEntityBundleInterface) {
      // Always use the default revision setting.
      $new_revision_default = $bundle_entity->shouldCreateNewRevision();
    }
    return $new_revision_default;
  }

  /**
   * Checks whether the revision form fields should be added to the form.
   *
   * @return bool
   *   TRUE if the form field should be added, FALSE otherwise.
   */
  protected function showRevisionUi() {
    return $this->entity->getEntityType()->showRevisionUi();
  }

}
