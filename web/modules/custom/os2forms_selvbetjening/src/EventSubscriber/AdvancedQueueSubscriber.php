<?php

declare(strict_types=1);

namespace Drupal\os2forms_selvbetjening\EventSubscriber;

use Drupal\advancedqueue\Event\AdvancedQueueEvents;
use Drupal\advancedqueue\Event\JobEvent;
use Drupal\webform\Entity\WebformSubmission;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * AdvancedQueueSubscriber.
 */
final class AdvancedQueueSubscriber implements EventSubscriberInterface {

  /**
   * Resets the static Webform state of WebformSubmissions before processing a
   * job.
   *
   * @see WebformSubmission::getWebform()
   * @see WebformSubmission::$webform
   */
  public function onPreProcess(JobEvent $event): void {
    try {
      $property = new \ReflectionProperty(WebformSubmission::class, 'webform');
      $property->setValue(NULL);
    }
    catch (\ReflectionException) {
      // Ignore any errors.
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      // We want our subscriber to run very early (cf.
      // https://www.drupal.org/docs/develop/creating-modules/subscribe-to-and-dispatch-events#s-event-subscriber-priorities)
      AdvancedQueueEvents::PRE_PROCESS => ['onPreProcess', 999_999],
    ];
  }

}
