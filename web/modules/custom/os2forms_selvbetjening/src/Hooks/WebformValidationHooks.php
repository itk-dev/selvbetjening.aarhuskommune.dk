<?php

namespace Drupal\os2forms_selvbetjening\Hooks;

use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\webform\Plugin\WebformHandlerInterface;
use Drupal\webform\WebformInterface;

/**
 * Webform validation.
 */
class WebformValidationHooks {
  use StringTranslationTrait;

  private const array FILE_ELEMENT_TYPES = [
    'managed_file', 'webform_image_file',
    'webform_audio_file', 'webform_video_file', 'webform_document_file',
  ];

  private const array EMAIL_HANDLER_PLUGIN_IDS = [
    'email',
  ];

  /**
   * IDs of form on which we want to show warnings.
   *
   * Note: For some reason messenger messages does not show up on all pages,
   * e.g. on the webform's general settings form (webform_settings_form), but we
   * include that ID for completeness.
   */
  private const array WEBFORM_FORM_IDS = [
    'webform_edit_form',
    'webform_handlers_form',
    'webform_settings_form',
  ];

  public function __construct(
    private readonly RouteMatchInterface $routeMatch,
    private readonly AdminContext $adminContext,
    private readonly MessengerInterface $messenger,
  ) {
  }

  /**
   * Implements hook_form_alter().
   *
   * @todo Add #[Hook('form_alter')] when upgrading to
   * Drupal 11 (cf. https://www.drupal.org/node/3442349).
   */
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id) {
    $route = $this->routeMatch->getRouteObject();
    if (!$this->adminContext->isAdminRoute($route)) {
      return;
    }

    if (in_array($form_id, self::WEBFORM_FORM_IDS, TRUE)) {
      $formObject = $form_state->getFormObject();
      if ($formObject instanceof EntityFormInterface) {
        $entity = $formObject->getEntity();
        if ($entity instanceof WebformInterface) {
          $this->showWebformWarnings($entity);
        }
      }
    }
  }

  /**
   * Show warnings on webform settings – or lack of settings ...
   */
  private function showWebformWarnings(WebformInterface $webform): void {
    if ($this->hasFileElement($webform) && $this->hasEmailHandler($webform)) {
      $settings = $webform->getThirdPartySettings('os2forms')['os2forms_email_handler'] ?? NULL;
      $enabled = $settings['enabled'] ?? FALSE;
      $recipients = trim((string) ($settings['email_recipients'] ?? NULL));
      if (!$enabled || '' === $recipients) {
        $this->messenger->addWarning(
          $this->t('This webform has an attachment element and an email handler, but notifications on large attachments are not enabled. Go to <a href=":settings_url">@settings » @general » @third_party_settings » OS2Forms » @os2forms_email_handler</a> and activate them.', [
            ':settings_url' => Url::fromRoute('entity.webform.settings', [
              'webform' => $webform->id(),
            ],
            [
              'fragment' => 'edit-third-party-settings-os2forms-os2forms-email-handler',
            ],
            )->toString(TRUE)->getGeneratedUrl(),
            '@settings' => $this->t('Settings'),
            '@general' => $this->t('General'),
            '@third_party_settings' => $this->t('Third party settings'),
            '@os2forms_email_handler' => $this->t('OS2Forms email handler'),
          ]));
      }
    }
  }

  /**
   * Get all file elements on a webform.
   */
  private function getFileElements(WebformInterface $webform): array {
    return array_filter(
      $webform->getElementsDecodedAndFlattened(),
      static fn (array $element): bool => in_array($element['#type'] ?? NULL, self::FILE_ELEMENT_TYPES, TRUE)
    );
  }

  /**
   * Decide if a webform has a file element.
   */
  private function hasFileElement(WebformInterface $webform): bool {
    return !empty($this->getFileElements($webform));
  }

  /**
   * Get email handlers on a webform.
   */
  private function getEmailHandlers(WebformInterface $webform): array {
    return array_filter(
      iterator_to_array($webform->getHandlers()->getIterator()),
      static fn(WebformHandlerInterface $handler): bool => in_array($handler->getPluginId(), self::EMAIL_HANDLER_PLUGIN_IDS, TRUE)
    );
  }

  /**
   * Decide if a webform has an email handler.
   */
  private function hasEmailHandler(WebformInterface $webform): bool {
    return !empty($this->getEmailHandlers($webform));
  }

}
