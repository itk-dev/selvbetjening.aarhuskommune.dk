<?php

namespace Drupal\os2forms_selvbetjening\Helper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Form Helper class, for altering forms.
 */
class FormHelper {
  use StringTranslationTrait;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user.
   */
  public function __construct(private readonly AccountInterface $account) {}

  /**
   * Allows altering of forms.
   */
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id) {
    // Add description to the message body section of the email handler.
    if ('webform_handler_form' === $form_id && 'email' === ($form['#webform_handler_plugin_id'] ?? NULL)) {
      // Email from description.
      $form['settings']['from']['#description'] = $this->t('If [site:mail] is used as sender address it will be sent from noreply@aarhus.dk. If it is changed: remember to use a shared mailbox(funktionspostkasse) and that we can only guarantee delivery of emails from a @aarhus.dk email.');

      // Email body description.
      $form['settings']['message']['body']['#description'] = $this->t('Use the default email body or define you own custom one. See <a href="https://os2forms.os2.eu/mail-tekster">OS2Forms Loop</a> for other standards and examples.');
    }

    $webform_category_description = $this->t('Externally: Citizen. Internally: Employees');

    // Webform general settings form.
    if ('webform_settings_form' === $form_id) {
      // Add description to category choice in Webform Settings.
      $form['general_settings']['category']['#description'] = $webform_category_description;
      // Disable access to ajax settings for non administrator users.
      if (!in_array('administrator', $this->account->getRoles())) {
        $form['ajax_settings']['#disabled'] = TRUE;
      }
    }

    // Add description to category choice when adding new Webform.
    if ('webform_add_form' === $form_id) {
      $form['category']['#description'] = $webform_category_description;
    }

    // Add logout suggestion to logged-in users
    // attempting to handle a maestro task.
    if ('maestro_interactive_form' === $form_id && isset($form['error']['#markup']) && $this->account->isAuthenticated()) {
      $markup = $form['error']['#markup'];
      if ($markup instanceof TranslatableMarkup) {
        $message = strtolower($markup->getUntranslatedString());
        if (str_contains($message, 'access') && str_contains($message, 'task')) {
          $currentUrl = Url::fromRoute('<current>')
            ->toString(TRUE)
            ->getGeneratedUrl();
          $logoutUrl = Url::fromRoute('user.logout', ['destination' => $currentUrl])
            ->toString(TRUE)
            ->getGeneratedUrl();

          $form['error'] = [
            '#type' => 'container',
            'error_message' => $form['error'] + [
              '#prefix' => '<div>',
              '#sufix' => '</div>',
            ],
            'suggestion' => [
              '#markup' => $this->t('Here is a suggestion to what you can do. Try <a href=":url">logging out</a>', [':url' => $logoutUrl]),
              '#prefix' => '<div>',
              '#sufix' => '</div>',
            ],
          ];
        }
      }
    }

    // Important: We must check the actual form ID on the form here; the
    // 'user_login_form' may have been replaced with 'openid_connect_login_form'
    // in openid_connect_form_user_login_form_alter (which see for details).
    if ('user_login_form' === $form['#form_id']) {
      // Remove all children and select stuff from login form.
      $keysToUnset = [
        ...Element::children($form),
        '#validate',
        '#submit',
      ];
      foreach ($keysToUnset as $key) {
        unset($form[$key]);
      }
      $form['message'] = [
        'message' => Link::createFromRoute($this->t('Login form has been disabled'), 'user.login')
          ->toRenderable(),
      ];
    }

    if ('template_edit_task' === $form_id) {
      $form['#validate'][] = '\Drupal\os2forms_selvbetjening\Helper\FormHelper::validateByContentFunction';
    }
  }

  /**
   * Validates form input by checking a user-defined function.
   *
   * This method ensures that:
   * - The specified function exists.
   * - The number of provided parameters matches
   * the defined function's requirements,
   *   adjusted for additional parameters added during execution.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateByContentFunction(array &$form, FormStateInterface $form_state): void {
    if ('bycontentfunction' === $form_state->getValue(['spv', 'method'])) {
      // Get function name and parameters defined in the flow task.
      $value = $form_state->getValue(['spv', 'variable_value']);

      // Split function name and parameters (format: function:param1,param2).
      $functionParts = explode(':', $value, 2);

      // Get function name and parameters.
      $functionName = $functionParts[0];
      $functionParams = isset($functionParts[1]) ? explode(',', $functionParts[1]) : [];

      // Get number of parameters.
      $paramCount = count($functionParams);

      if (!function_exists($functionName)) {
        $form_state->setError($form['spv'], t('Function %function_name does not exist', ['%function_name' => $functionName]));
        return;
      }

      // Get the number of parameters for the defined function.
      try {
        $functionParamCount = (new \ReflectionFunction($functionName))->getNumberOfRequiredParameters();
      }
      catch (\ReflectionException $e) {
        $form_state->setError(
          $form['spv'],
          t('Invalid function %function_name', [
            '%function_name' => $functionName,
          ])
        );
        \Drupal::logger('os2forms_selvbetjening')
          ->error('Error reflecting function %function_name: %message', [
            '%function_name' => $functionName,
            '%message' => $e->getMessage(),
            // Add the full exception to the context for future reference.
            'exception' => $e,
          ]);
        return;
      }

      // The maestro execute method always adds 2 parameters
      // (queueID and processID) when handling the "bycontentfunction" case.
      // @see MaestroSetProcessVariableTask::execute()
      $functionParamCount -= 2;

      if ($functionParamCount < 0) {
        $form_state->setError($form['spv'], t('Function %function_name is required to take at least 2 arguments.', ['%function_name' => $functionName]));
        return;
      }

      // Check if the number of parameters matches.
      if ($paramCount !== $functionParamCount) {
        $form_state->setError(
          $form['spv'],
          t(
            'Function %function_name expects %function_parameter_count parameters. %parameter_defined_count given.',
            [
              '%function_name' => $functionName,
              '%function_parameter_count' => $functionParamCount,
              '%parameter_defined_count' => $paramCount,
            ]
          )
        );
      }
      if (isset($form['#id']) && 'views-exposed-form-os2forms-failed-jobs-personalized-block-1' === $form['#id']) {
        $form['#attached']['library'][] = 'os2forms_selvbetjening/exposed-form-display';
      }
    }
  }

}
