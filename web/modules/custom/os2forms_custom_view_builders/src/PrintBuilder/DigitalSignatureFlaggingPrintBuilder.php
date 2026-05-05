<?php

namespace Drupal\os2forms_custom_view_builders\PrintBuilder;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\entity_print\Plugin\PrintEngineInterface;
use Drupal\entity_print\Renderer\RendererFactoryInterface;
use Drupal\os2forms_attachment\Os2formsAttachmentPrintBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Decorates the OS2Forms attachment print builder.
 *
 * Sets a request attribute while a digital-signature attachment element is
 * being rendered to PDF, so other code (post-render subscriber, view builder)
 * can detect that exact context.
 */
final class DigitalSignatureFlaggingPrintBuilder extends Os2formsAttachmentPrintBuilder {

  public const REQUEST_ATTRIBUTE = '_os2forms_digital_signature_attachment';

  public function __construct(
    RendererFactoryInterface $renderer_factory,
    EventDispatcherInterface $event_dispatcher,
    TranslationInterface $string_translation,
    FileSystemInterface $file_system,
    private readonly RequestStack $requestStack,
  ) {
    parent::__construct($renderer_factory, $event_dispatcher, $string_translation, $file_system);
  }

  /**
   * {@inheritdoc}
   *
   * @param array $entities
   *   The entities to print.
   * @param \Drupal\entity_print\Plugin\PrintEngineInterface $print_engine
   *   The print engine.
   * @param string $scheme
   *   Stream wrapper scheme to save to.
   * @param string|false $filename
   *   Target filename, or FALSE to derive one.
   * @param bool $use_default_css
   *   Whether to include the default CSS.
   * @param string $signaturePosition
   *   Position for the digital signature validation text.
   */
  public function savePrintableDigitalSignature(array $entities, PrintEngineInterface $print_engine, $scheme = 'public', $filename = FALSE, $use_default_css = TRUE, string $signaturePosition = self::SIGNATURE_POSITION_AFTER_CONTENT) {
    $request = $this->requestStack->getCurrentRequest();
    $request?->attributes->set(self::REQUEST_ATTRIBUTE, TRUE);
    try {
      return parent::savePrintableDigitalSignature($entities, $print_engine, $scheme, $filename, $use_default_css, $signaturePosition);
    }
    finally {
      $request?->attributes->remove(self::REQUEST_ATTRIBUTE);
    }
  }

}
