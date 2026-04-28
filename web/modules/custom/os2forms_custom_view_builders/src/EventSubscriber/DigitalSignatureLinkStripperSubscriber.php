<?php

namespace Drupal\os2forms_custom_view_builders\EventSubscriber;

use Drupal\entity_print\Event\PrintEvents;
use Drupal\entity_print\Event\PrintHtmlAlterEvent;
use Drupal\os2forms_custom_view_builders\PrintBuilder\DigitalSignatureFlaggingPrintBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Strips anchor tags from PDFs generated for digital-signature attachments.
 */
final readonly class DigitalSignatureLinkStripperSubscriber implements EventSubscriberInterface {

  public function __construct(private RequestStack $requestStack) {
  }

  /**
   * Strip <a> tags from the rendered HTML, preserving inner content.
   */
  public function onPrintRender(PrintHtmlAlterEvent $event): void {
    $request = $this->requestStack->getCurrentRequest();
    if (!$request?->attributes->get(DigitalSignatureFlaggingPrintBuilder::REQUEST_ATTRIBUTE)) {
      return;
    }
    $html = &$event->getHtml();
    // Strip <a> tags from the rendered HTML, preserving inner content.
    $html = preg_replace('@<a\b[^>]*>(.*?)</a>@is', '$1', $html);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      PrintEvents::POST_RENDER => ['onPrintRender'],
    ];
  }

}
