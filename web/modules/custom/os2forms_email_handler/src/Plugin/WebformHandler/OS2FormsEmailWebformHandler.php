<?php

namespace Drupal\os2forms_email_handler\Plugin\WebformHandler;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Site\Settings;
use Drupal\os2forms_email_handler\Helper\WebformHelper;
use Drupal\os2web_audit\Service\Logger;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Emails a webform submission.
 *
 * @WebformHandler(
 *   id = "email",
 *   label = @Translation("OS2Forms email"),
 *   category = @Translation("Notification"),
 *   description = @Translation("Sends a webform submission via an email."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   tokens = TRUE,
 * )
 */
class OS2FormsEmailWebformHandler extends EmailWebformHandler implements ContainerFactoryPluginInterface {
  /**
   * File element types.
   */
  private const FILE_ELEMENT_TYPES = [
    'webform_image_file',
    'webform_document_file',
    'webform_video_file',
    'webform_audio_file',
    'managed_file',
  ];

  private const DEFAULT_ATTACHMENT_FILE_SIZE_THRESHOLD = '2MB';
  private const DEFAULT_FROM_EMAIL = 'noreply@aarhus.dk';
  private const DEFAULT_FROM_NAME = 'Selvbetjening';

  /**
   * The audit logger.
   *
   * @var \Drupal\os2web_audit\Service\Logger
   */
  protected Logger $auditLogger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->auditLogger = $container->get('os2web_audit.logger');
    return $instance;
  }

  /**
   * Sends extra notification based on attachment file size.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $message
   *   An array of message parameters.
   */
  public function sendMessage(WebformSubmissionInterface $webform_submission, array $message) {
    $webform = $webform_submission->getWebform();
    $settings = $webform->getThirdPartySetting('os2forms', WebformHelper::MODULE_NAME);

    $sendOriginalMessage = TRUE;

    if (isset($settings['enabled']) && $settings['enabled'] && !empty($settings['email_recipients'])) {
      $sendOriginalMessage = !$this->handleAttachmentNotification($webform_submission, $message, $settings['email_recipients']);
    }

    if ($sendOriginalMessage) {
      $result = parent::sendMessage($webform_submission, $message);

      if ($result) {
        $msg = sprintf('Email, %s, sent to %s. Webform id %s.', $message['subject'], $message['to_mail'], $webform_submission->getWebform()->id());
        $this->auditLogger->info('Email', $msg);
      }

      return $result;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Handles attachment notification on submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $message
   *   An array of message parameters.
   * @param string $emails
   *   A string of emails.
   *
   * @return bool
   *   Whether file size threshold was surpassed or not.
   */
  private function handleAttachmentNotification(WebformSubmissionInterface $webform_submission, array $message, string $emails): bool {
    if ($isFileSizeThresholdSurpassed = $this->isAttachmentFileSizeThresholdSurpassed($webform_submission)) {
      $this->sendFileSizeNotification($webform_submission, $message, $emails);
    }

    return $isFileSizeThresholdSurpassed;
  }

  /**
   * Checks whether file size threshold is surpassed by submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return bool
   *   Whether threshold is surpassed or not.
   */
  private function isAttachmentFileSizeThresholdSurpassed(WebformSubmissionInterface $webform_submission): bool {
    // Determine file size threshold in bytes.
    $settings = Settings::get(WebformHelper::MODULE_NAME);
    $threshold = $settings['notification_file_size_threshold'] ?? self::DEFAULT_ATTACHMENT_FILE_SIZE_THRESHOLD;
    $thresholdInBytes = $this->convertToBytes($threshold);

    $totalSize = $this->getTotalAttachmentFileSize($webform_submission);

    return $totalSize > $thresholdInBytes;
  }

  /**
   * Gets array of file ids in submission that are attached in email.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   A webform submission.
   *
   * @return array
   *   File ids.
   */
  private function getFileIdsFromSubmission(WebformSubmissionInterface $submission): array {
    $elements = $submission->getWebform()->getElementsDecodedAndFlattened();

    // Removed excluded elements.
    $excludedElements = $this->configuration['excluded_elements'];

    foreach ($excludedElements as $key => $excludedElement) {
      if (array_key_exists($key, $elements)) {
        unset($elements[$key]);
      }
    }

    $fileElements = array_map(
      fn ($type) => $this->getElementsByType($type, $elements),
      self::FILE_ELEMENT_TYPES
    );

    // Flatten the array.
    // https://dev.to/klnjmm/never-use-arraymerge-in-a-for-loop-in-php-5go1
    $fileElements = array_merge(...$fileElements);

    $elementKeys = array_keys($fileElements);

    $fileIds = [];

    foreach ($elementKeys as $elementKey) {
      if (empty($submission->getData()[$elementKey])) {
        continue;
      }

      // Convert occurrences of singular file into array.
      $elementFileIds = (array) $submission->getData()[$elementKey];

      $fileIds[] = $elementFileIds;
    }

    return array_merge(...$fileIds);
  }

  /**
   * Get elements by type.
   *
   * @param string $type
   *   The type of elements wanted.
   * @param array $elements
   *   Array of elements.
   *
   * @return array
   *   Available elements.
   */
  private function getElementsByType(string $type, array $elements): array {
    $attachmentElements = array_filter($elements, function ($element) use ($type) {
      return $type === $element['#type'];
    });

    return array_map(function ($element) {
      return $element['#title'];
    }, $attachmentElements);
  }

  /**
   * Converts threshold to bytes.
   *
   * @param string $threshold
   *   File size threshold.
   *
   * @return int
   *   Threshold in bytes.
   *
   * @see https://stackoverflow.com/questions/11807115/php-convert-kb-mb-gb-tb-etc-to-bytes
   */
  private function convertToBytes(string $threshold): int {
    // Allowed units.
    $units = ['KB', 'MB', 'GB'];

    // Get number of units and units from threshold.
    preg_match('/(?<num>\d+)(?<units>kb|mb|gb)$/i', $threshold, $matches);

    $size = (int) $matches['num'];
    $unit = strtoupper($matches['units']);

    // Number of times 1024 should be multiplied on based on unit.
    // KB, multiply by 1024 once
    // MB, multiply by 1024 twice, etc.
    $unitExponentMultiplier = ((int) array_search($unit, $units)) + 1;

    return $size * (1024 ** $unitExponentMultiplier);

  }

  /**
   * Sends file size notification message.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $message
   *   An array of message parameters.
   * @param string $emails
   *   A string of emails.
   */
  private function sendFileSizeNotification(WebformSubmissionInterface $webform_submission, array $message, string $emails): void {
    $emails = array_filter(array_map('trim', explode(PHP_EOL, $emails)));

    foreach ($emails as $emailAddress) {

      $context = [
        '@form' => $this->getWebform()->label(),
        '@form_id' => $this->getWebform()->id(),
        '@handler' => $this->label(),
        '@handler_id' => $this->getHandlerId(),
        '@email' => $emailAddress,
        'link' => ($webform_submission->id()) ? $webform_submission->toLink('#' . $webform_submission->serial())->toString() : NULL,
        'webform_submission' => $webform_submission,
        'operation' => 'notification email',
      ];

      // Check if email is invalid.
      if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {

        if ($webform_submission->getWebform()->hasSubmissionLog()) {
          // Log detailed message to the 'webform_submission' log.
          $this->getLogger('webform_submission')->notice("Email notification advising surpassed file sizes could not be sent to '@email'.", $context);
        }

      }
      else {

        $settings = Settings::get(WebformHelper::MODULE_NAME);
        $notificationMessage = $this->defaultConfiguration();

        $notificationMessage['to_mail'] = $emailAddress;
        $notificationMessage['subject'] = $this->t('File size submission warning');

        $notificationMessage['body'] = $this->t(
          '<p>Dear @name</p><p>Submission @submission attempted sending an email with a large total file size of attachments surpassing @threshold for handler @handler (@handler_id) on form @form (@form_id).</p>', [
            '@name' => $emailAddress,
            '@submission' => $context['link'],
            '@handler' => $context['@handler'],
            '@handler_id' => $context['@handler_id'],
            '@form' => $context['@form'],
            '@form_id' => $context['@form_id'],
            '@threshold' => $settings['notification_file_size_threshold'] ?? self::DEFAULT_ATTACHMENT_FILE_SIZE_THRESHOLD,
          ]);

        $notificationMessage['from_mail'] = $settings['notification_message_from_email'] ?? self::DEFAULT_FROM_EMAIL;
        $notificationMessage['from_name'] = $settings['notification_message_from_name'] ?? self::DEFAULT_FROM_NAME;
        $notificationMessage['webform_submission'] = $message['webform_submission'];
        $notificationMessage['handler'] = $message['handler'];

        $result = parent::sendMessage($webform_submission, $notificationMessage);

        if ($result) {
          $msg = sprintf('Email, %s, sent to %s. Webform id %s.', $notificationMessage['subject'], $notificationMessage['to_mail'], $webform_submission->getWebform()->id());
          $this->auditLogger->info('Email', $msg);
        }

        if ($webform_submission->getWebform()->hasSubmissionLog() && $result) {
          // Log detailed message to the 'webform_submission' log.
          $this->getLogger('webform_submission')->notice("Email notification advising surpassed file sizes sent to '@email'.", $context);
        }

      }
    }
  }

  /**
   * Gets total attachment file size.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  private function getTotalAttachmentFileSize(WebformSubmissionInterface $webform_submission): int {
    $fileElementIds = $this->getFileIdsFromSubmission($webform_submission);

    return array_reduce($fileElementIds, function ($carry, $item) {
      return $carry + (int) $this->entityTypeManager->getStorage('file')->load($item)->getSize();
    }, 0);
  }

}
