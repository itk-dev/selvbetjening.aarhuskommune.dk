<?php

namespace Drupal\webform_revisions\Entity;

use Drupal\Component\Utility\Crypt;
use Drupal\config_entity_revisions\ConfigEntityRevisionsInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform_revisions\Controller\WebformRevisionsController;

/**
 * Overrides for the WebformSubmission class to add revisions support.
 */
class WebformRevisionsSubmission extends WebformSubmission {

  /**
   * {@inheritdoc}
   */
  public function getWebform() {
    $forms = &drupal_static('webforms', []);

    if ($this->webform) {
      return $this->webform;
    }
    $webform_id = $this->webform_id->target_id;
    $revision_id = $this->webform_revision[0] ?? NULL;
    if ($revision_id) {
      $revision_id = $revision_id->target_id ?: NULL;
      if (!empty($forms[$webform_id]) && !empty($forms[$webform_id][$revision_id])) {
        $this->webform = $forms[$webform_id][$revision_id];
        return $forms[$webform_id][$revision_id];
      }
    }
    else {
      // If the webform is new, the revision ID should be the currently
      // published webform revision. If it is not new, it should be the first
      // webform revision - the submission existed before revisions were
      // enabled.
      $defaultWebform = $this->webform_id->entity;

      if (!$this->isNew()) {
        // Avoid repeated queries.
        if (!empty($forms[$webform_id]['original'])) {
          $revision_id = $forms[$webform_id]['original'];
        }
        else {
          $revisionsController = WebformRevisionsController::create(\Drupal::getContainer());
          $revisionIds = $revisionsController->getRevisionIds($defaultWebform->getContentEntityId());
          $revision_id = current($revisionIds);

          $forms[$webform_id]['original'] = $revision_id;
        }
      }
    }

    if (empty($forms[$webform_id])) {
      $forms[$webform_id] = [];
    }

    if ($revision_id && empty($forms[$webform_id][$revision_id])) {
      $revisionsController = WebformRevisionsController::create(\Drupal::getContainer());
      $load_revision_id = $this->isDraft() ?
        $revisionsController->checkUpdateRevision($revision_id, $webform_id) :
        $revision_id;
      $webform = $revisionsController->loadConfigEntityRevision($load_revision_id, $webform_id);
      $forms[$webform_id][$revision_id] = $webform;
    }
    elseif (isset($this->webform_id->entity)) {
      $forms[$webform_id][NULL] = $this->webform_id->entity;
    }
    else {
      $forms[$webform_id][NULL] = static::$webform;
    }

    $this->webform_revision = $revision_id;
    $this->webform = $forms[$webform_id][$revision_id];
    return $forms[$webform_id][$revision_id];
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    if (empty($values['webform_id']) && empty($values['webform'])) {
      throw new \Exception('A webform id or instance is required to create a webform submission.');
    }

    // Get temporary webform entity and store it in the static
    // WebformSubmission::$webform property. This could be reworked to use
    // \Drupal\Core\TempStore\PrivateTempStoreFactory but it might be overkill
    // since we are just using this to validate that a webform's elements can be
    // rendered.
    // @see \Drupal\webform\WebformEntityElementsValidator::validateRendering()
    // @see \Drupal\webform_ui\Form\WebformUiElementTestForm::buildForm()
    if (isset($values['webform']) && ($values['webform'] instanceof ConfigEntityRevisionsInterface)) {
      $webform = $values['webform'];
      static::$webform = $values['webform'];
      $values['webform_id'] = $values['webform']->id();
    }
    else {
      /** @var ConfigEntityRevisionsControllerInterface $revisionsController */
      $revisionsController = WebformRevisionsController::create(\Drupal::getContainer());

      /** @var \Drupal\webform\WebformInterface $webform */
      $webform = $revisionsController->loadConfigEntityRevision();

      if (!empty($values['webform_id']) && (!$webform || $values['webform_id'] !== $webform->id())) {
        $webform = \Drupal::entityTypeManager()->getStorage('webform')
          ->load($values['webform_id']);
      }
      static::$webform = NULL;
    }

    // Get request's source entity parameter.
    /** @var \Drupal\webform\WebformRequestInterface $request_handler */
    $request_handler = \Drupal::service('webform.request');
    $source_entity = $request_handler->getCurrentSourceEntity('webform');
    $values += [
      'entity_type' => ($source_entity) ? $source_entity->getEntityTypeId() : NULL,
      'entity_id' => ($source_entity) ? $source_entity->id() : NULL,
      'webform_revision' => $webform->getRevisionId(),
    ];

    // Decode all data in an array.
    if (empty($values['data'])) {
      $values['data'] = [];
    }
    elseif (is_string($values['data'])) {
      $values['data'] = Yaml::decode($values['data']);
    }

    // Get default date from source entity 'webform' field.
    if ($values['entity_type'] && $values['entity_id']) {
      $source_entity = \Drupal::entityTypeManager()
        ->getStorage($values['entity_type'])
        ->load($values['entity_id']);

      /** @var \Drupal\webform\WebformEntityReferenceManagerInterface $entity_reference_manager */
      $entity_reference_manager = \Drupal::service('webform.entity_reference_manager');

      if ($webform_field_name = $entity_reference_manager->getFieldName($source_entity)) {
        if ($source_entity->$webform_field_name->target_id == $webform->id() && $source_entity->$webform_field_name->default_data) {
          $values['data'] += Yaml::decode($source_entity->$webform_field_name->default_data);
        }
      }
    }

    // Set default values.
    $current_request = \Drupal::requestStack()->getCurrentRequest();
    $values += [
      'in_draft' => FALSE,
      'uid' => \Drupal::currentUser()->id(),
      'langcode' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
      'token' => Crypt::randomBytesBase64(),
      'uri' => preg_replace('#^' . base_path() . '#', '/', $current_request->getRequestUri()),
      'remote_addr' => ($webform && $webform->isConfidential()) ? '' : $current_request->getClientIp(),
    ];

    $webform->invokeHandlers(__FUNCTION__, $values);
    $webform->invokeElements(__FUNCTION__, $values);
  }

}
