<?php

namespace Drupal\os2forms_queued_email\Plugin\Mail;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Mail\Attribute\Mail;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\advancedqueue\Job;
use Drupal\os2forms_queued_email\Plugin\AdvancedQueue\JobType\QueuedEmail;
use Drupal\smtp\Plugin\Mail\SMTPMailSystem;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Extends the SMTPMailSystem plugin to use a queue.
 */
#[Mail(
  id: 'queued_smtp_php_mail',
  label: new TranslatableMarkup('Queued SMTP PHP mailer'),
  description: new TranslatableMarkup("Queues a job to send the mail using SMTP mailer's format() method."),
)]
final class QueuedSmtpPhpMail extends SMTPMailSystem {

  /**
   * The submission logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $submissionLogger;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    LoggerChannelInterface $logger,
    MessengerInterface $messenger,
    EmailValidatorInterface $emailValidator,
    ConfigFactoryInterface $config_factory,
    AccountProxyInterface $account,
    FileSystemInterface $file_system,
    MimeTypeGuesserInterface $mime_type_guesser,
    RendererInterface $renderer,
    SessionInterface $session,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $messenger, $emailValidator, $config_factory, $account, $file_system, $mime_type_guesser, $renderer, $session);
    $this->submissionLogger = $loggerFactory->get('webform_submission');
  }

  /**
   * Creates an instance of queued smtp php mail.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('smtp.logger'),
      $container->get('messenger'),
      $container->get('email.validator'),
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('file_system'),
      $container->get('file.mime_type.guesser'),
      $container->get('renderer'),
      $container->get('session'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
    );
  }

  /**
   * Enqueue job.
   */
  public function mail(array $message) {

    try {
      $submission = $message['params']['webform_submission'];

      $path = QueuedEmail::OS2FORMS_QUEUED_EMAIL_FILE_PATH;
      $this->fileSystem->prepareDirectory($path);

      // Save a copy of OS2Forms attachment in filesystem.
      foreach ($message['params']['attachments'] as &$attachment) {
        if (!isset($attachment[QueuedEmail::OS2FORMS_QUEUED_EMAIL_IS_STATIC_FILE])) {
          $newFilename = uniqid() . $attachment['filename'];
          $privateFilepath = $path . '/' . $newFilename;
          file_put_contents($privateFilepath, $attachment[QueuedEmail::FILECONTENT]);
          $attachment[QueuedEmail::OS2FORMS_QUEUED_EMAIL_CONFIG_NAME] = $privateFilepath;
          $attachment[QueuedEmail::FILECONTENT] = '';
        }
      }

      // These are not needed in the later stages of email sending.
      unset($message['params']['webform_submission']);
      unset($message['params']['handler']);

      $queueStorage = $this->entityTypeManager->getStorage('advancedqueue_queue');
      /** @var \Drupal\advancedqueue\Entity\Queue $queue */
      $queue = $queueStorage->load('os2forms_queued_email');
      $job = Job::create(QueuedEmail::class, [
        // Add information already contained in message for debugging purposes.
        'id' => $message['id'],
        'module' => $message['module'],
        'key' => $message['key'],
        'to' => $message['to'],
        'subject' => $message['subject'],
        // Note that submissions may not exist later when job is processed.
        'submissionId' => $submission->id(),
        'webformId' => $submission->getWebform()->id(),
        'message' => json_encode($message),
      ]);

      $queue->enqueueJob($job);

      $logger_context = [
        'handler_id' => 'os2forms_queued_email',
        'channel' => 'webform_submission',
        'webform_submission' => $submission,
        'operation' => 'submission queued',
      ];

      $this->submissionLogger->notice($this->t('Added submission #@serial to queue for processing', [
        '@serial' => $submission->serial(),
      ]), $logger_context);

    }
    catch (\Exception $e) {

      $logger_context = [
        'handler_id' => 'os2forms_queued_email',
        'channel' => 'webform_submission',
        'webform_submission' => $submission ?? NULL,
        'operation' => 'failed queueing submission',
      ];

      $this->submissionLogger->error($this->t('The submission #@serial failed (@message)', [
        '@serial' => $submission?->serial(),
        '@message' => $e->getMessage(),
      ]), $logger_context);

      return FALSE;
    }

    return TRUE;
  }

}
