<?php

namespace Drupal\itkdev_booking\Plugin\WebformElement;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\itkdev_booking\Helper\UserHelper;
use Drupal\webform\Plugin\WebformElement\Hidden;
use Drupal\Core\Site\Settings;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Provides a webform 'booking_element'.
 *
 * @WebformElement(
 *   id = "booking_element",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Hidden.php/class/Hidden",
 *   label = @Translation("Booking"),
 *   description = @Translation("Provides a booking form element."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class BookingElement extends Hidden {

  private ModuleExtensionList $extensionList;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->extensionList = $container->get('extension.list.module');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'rooms' => [],
      'enable_booking' => FALSE,
      'enable_resource_tooltips' => FALSE,
      'booking_mode' => '',
      'create_booking_url' => '',
      'change_booking_url' => '',
      'step1' => FALSE,
      'redirect_url' => '',
      'info_box_color' => '',
      'info_box_header' => '',
      'info_box_content' => '',
    ] + parent::defineDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    // Hidden elements should never get a test value.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['element']['booking_mode'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this
        ->t('Form mode'),
      '#options' => [
        'create_booking_mode' => $this
          ->t('Create booking mode'),
        'change_booking_mode' => $this
          ->t('List bookings mode'),
      ],
      '#description' => $this
        ->t('Use the element for either creating a new booking or view a list of existing bookings.'),
    ];

    $form['element']['create_booking_url'] = [
      '#type' => 'url',
      '#title' => $this
        ->t('Create booking url'),
      '#description' => $this
        ->t('This is needed for the app menu to work properly.'),
    ];

    $form['element']['change_booking_url'] = [
      '#type' => 'url',
      '#title' => $this
        ->t('List bookings url'),
      '#description' => $this
        ->t('This is needed for the app menu to work properly.'),
    ];

    $form['element']['step1'] = [
      '#type' => 'checkbox',
      '#title' => $this
        ->t('View only (No booking)'),
      '#description' => $this
        ->t('If checked the form will perform a redirect with booking parameters when booking choices are made.'),
      '#states' => [
        'visible' => [
          ':input[name="properties[booking_mode]"]' => ['value' => 'create_booking_mode'],
        ],
      ],
    ];

    $form['element']['redirect_url'] = [
      '#type' => 'url',
      '#title' => $this
        ->t('Redirect url'),
      '#states' => [
        'visible' => [
          ':input[name="properties[step1]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['element']['info_box_color'] = [
      '#type' => 'url',
      '#title' => $this
        ->t('Info boks baggrundsfarve (HEXCODE - f.eks. #0c6efd)'),
    ];

    $form['element']['info_box_header'] = [
      '#type' => 'url',
      '#title' => $this
        ->t('Info boks titel'),
    ];

    $form['element']['info_box_content'] = [
      '#type' => 'url',
      '#title' => $this
        ->t('Info boks indhold'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$element, array &$form, FormStateInterface $form_state) {
    $params = [
      'api_endpoint' => Settings::get('itkdev_booking_api_endpoint_frontend'),
      'front_page_url' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
      'license_key' => Settings::get('itkdev_booking_fullcalendar_license'),
      'output_field_id' => 'submit-values',
      'create_booking_mode' => $element['#booking_mode'] === 'create_booking_mode',
      'create_booking_url' => $element['#create_booking_url'] ?? NULL,
      'change_booking_url' => $element['#change_booking_url'] ?? NULL,
      'step_one' => isset($element['#step1']),
      'redirect_url' => $element['#redirect_url'] ?? NULL,
      'info_box_color' => $element['info_box_color'] ?? NULL,
      'info_box_header' => $element['info_box_header'] ?? NULL,
      'info_box_content' => $element['info_box_content'] ?? NULL,
      'df_map_username' => Settings::get('itkdev_booking_df_map_username'),
      'df_map_password' => Settings::get('itkdev_booking_df_map_password'),
    ];

    $prefix = twig_render_template($this->extensionList->getPath('itkdev_booking') . '/templates/booking_app.html.twig', [
      'params' => $params,
      // Needed to prevent notices when Twig debugging is enabled.
      'theme_hook_original' => 'not-applicable',
    ]);

    if ('booking_element' == $element['#type']) {
      $form['#attached']['library'][] = 'itkdev_booking/booking_app';
      $form['#attached']['drupalSettings']['booking_app'] = $params;
      $form['elements'][$element['#webform_key']]['#prefix'] = $prefix;
      $form['elements'][$element['#webform_key']]['#attributes']['id'] = 'submit-values';
      $form['elements'][$element['#webform_key']]['#default_value'] = json_encode([]);
      $form['#validate'][] = [$this, 'validateBooking'];
    }
  }

  /**
   * Attach userId and name to webform submit.
   *
   * @throws \JsonException
   */
  public function preSave(array &$element, WebformSubmissionInterface $webform_submission) {
    $request = \Drupal::request();
    $userHelper = new UserHelper();

    $userArray = $userHelper->getUserValues($request);

    foreach ($webform_submission->getWebform()->getElementsDecoded() as $elementName => $element) {
      if ('booking_element' === $element['#type']) {
        $data = json_decode($webform_submission->getData()[$elementName], TRUE, 512, JSON_THROW_ON_ERROR);

        $data['name'] = $userArray['name'];
        $data['userId'] = $userArray['userId'];
        $data['userPermission'] = $userArray['permission'];
        $data['whitelistKey'] = $userArray['whitelistKey'];
        $data['formElement'] = 'booking_element';

        $webform_submission->setElementData($elementName, json_encode($data));
      }
    }

    parent::preSave($element, $webform_submission);
  }

  /**
   * Validate booking data in hidden field.
   *
   * @param $form
   *   The form to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   */
  public function validateBooking(&$form, FormStateInterface $form_state) {
    $elements = $form['elements'];
    foreach ($elements as $key => $form_element) {
      if (is_array($form_element) && isset($form_element['#type']) && 'booking_element' === $form_element['#type']) {
        try {
          $bookingValues = json_decode($form_state->getValues()[$key], TRUE, 512, JSON_THROW_ON_ERROR);
        }
        catch (\JsonException $exception) {
          $form_state->setError($form, t('Error in decoding booking data.'));
          return;
        }

        foreach ($bookingValues as $bookingKey => $bookingValue) {
          if (empty($bookingValue)) {
            switch ($bookingKey) {
              case 'subject':
                $form_state->setError($form, t('Error in "Booking title"'));
                break;

              case 'email':
                $form_state->setError($form, t('Error in "Your email"'));
                break;

              case 'resourceId':
                $form_state->setError($form, t('Error in booking selection resource id'));
              case 'start':
                $form_state->setError($form, t('Error in booking selection resource id'));
              case 'end':
                $form_state->setError($form, t('Error in booking selection'));
                break;

              default:
            }
          }
        }
      }
    }
  }

}
