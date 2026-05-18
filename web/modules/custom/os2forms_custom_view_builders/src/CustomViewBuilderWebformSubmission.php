<?php

namespace Drupal\os2forms_custom_view_builders;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Render\Element;
use Drupal\os2forms_custom_view_builders\PrintBuilder\DigitalSignatureFlaggingPrintBuilder;
use Drupal\webform\Twig\WebformTwigExtension;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformYaml;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionViewBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a class override webform submission view builder.
 *
 * @internal
 * This file is pretty much a copy and paste of the default view builder. The
 * few alterations are marked with "@internal OS2Forms changes start/end"
 * The file is ignored by phpstan because it is a copy of externally contributed
 * code.
 *
 * @see \Drupal\webform\Entity\WebformSubmission
 */
class CustomViewBuilderWebformSubmission extends WebformSubmissionViewBuilder {

  /**
   * The request stack.
   */
  protected RequestStack $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->requestStack = $container->get('request_stack');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    if (empty($entities)) {
      return;
    }

    /** @var \Drupal\webform\WebformSubmissionInterface[] $entities */
    foreach ($entities as $id => $webform_submission) {
      $webform = $webform_submission->getWebform();

      if ($view_mode === 'preview') {
        $options = [
          'view_mode' => $view_mode,
          'excluded_elements' => $webform->getSetting('preview_excluded_elements'),
          'exclude_empty' => $webform->getSetting('preview_exclude_empty'),
          'exclude_empty_checkbox' => $webform->getSetting('preview_exclude_empty_checkbox'),
        ];
      }
      else {
        // Track PDF.
        // @see webform_entity_print.module
        $route_name = $this->routeMatch->getRouteName();
        $pdf = in_array($route_name, ['entity_print.view.debug', 'entity_print.view']) || \Drupal::request()->request->get('_webform_entity_print');
        $options = [
          'view_mode' => $view_mode,
          'excluded_elements' => $webform->getSetting('submission_excluded_elements'),
          'exclude_empty' => $webform->getSetting('submission_exclude_empty'),
          'exclude_empty_checkbox' => $webform->getSetting('submission_exclude_empty_checkbox'),
          'pdf' => $pdf,
        ];
      }

      switch ($view_mode) {
        case 'twig':
          // @see \Drupal\webform_entity_print_attachment\Element\WebformEntityPrintAttachment::getFileContent
          $build[$id]['data'] = WebformTwigExtension::buildTwigTemplate(
            $webform_submission,
            $webform_submission->webformViewModeTwig
          );
          break;

        case 'yaml':
          // Note that the YAML view ignores all access controls and excluded
          // settings.
          $data = $webform_submission->toArray(TRUE, TRUE);
          // Covert computed element value markup to strings to
          // 'Object support when dumping a YAML file has been disabled' errors.
          WebformElementHelper::convertRenderMarkupToStrings($data);
          $build[$id]['data'] = [
            '#theme' => 'webform_codemirror',
            '#code' => WebformYaml::encode($data),
            '#type' => 'yaml',
          ];
          break;

        case 'text':
          $elements = $webform->getElementsInitialized();
          $build[$id]['data'] = [
            '#theme' => 'webform_codemirror',
            '#code' => $this->buildElements($elements, $webform_submission, $options, 'text'),
          ];
          break;

        case 'table':
          /* @internal OS2Forms changes start */
          $elements = $webform->getElementsInitializedAndFlattened();
          if ($this->isDigitalSignatureAttachmentRender()) {
            $this->overrideFormatsForPdf($elements);
          }
          /* @internal OS2Forms changes end */
          $build[$id]['data'] = $this->buildTable($elements, $webform_submission, $options);
          break;

        default:
        case 'html':
          $elements = $webform->getElementsInitialized();
          /* @internal OS2Forms changes start */
          if ($this->isDigitalSignatureAttachmentRender()) {
            $this->overrideFormatsForPdf($elements);
          }
          /* @internal OS2Forms changes end */
          $build[$id]['data'] = $this->buildElements($elements, $webform_submission, $options);
          break;
      }
    }

    EntityViewBuilder::buildComponents($build, $entities, $displays, $view_mode);
  }

  /**
   * Whether the current request is rendering a digital-signature attachment.
   */
  private function isDigitalSignatureAttachmentRender(): bool {
    $request = $this->requestStack->getCurrentRequest();

    return (bool) $request?->attributes->get(DigitalSignatureFlaggingPrintBuilder::REQUEST_ATTRIBUTE);
  }

  /**
   * Override element display formats for PDF rendering (no links).
   *
   * When rendering webform submissions as PDF attachments for digital
   * signature, element values must not be displayed as clickable links.
   *
   * @param array $elements
   *   The webform elements array (nested or flat).
   */
  private function overrideFormatsForPdf(array &$elements): void {
    $formatOverrides = [
      'managed_file' => 'name',
      'webform_document_file' => 'name',
      'webform_audio_file' => 'name',
      'webform_video_file' => 'name',
      'webform_image_file' => 'name',
    ];

    foreach (Element::children($elements) as $key) {
      $type = $elements[$key]['#type'] ?? '';
      if (isset($formatOverrides[$type])) {
        $elements[$key]['#format'] = $formatOverrides[$type];
      }
      // Recurse for nested child elements.
      $this->overrideFormatsForPdf($elements[$key]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildTable(array $elements, WebformSubmissionInterface $webform_submission, array $options = []) {
    $rows = [];
    foreach ($elements as $key => $element) {
      if (!$this->isElementVisible($element, $webform_submission, $options)) {
        continue;
      }

      /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
      $webform_element = $this->elementManager->getElementInstance($element);

      // Replace tokens before building the element.
      $webform_element->replaceTokens($element, $webform_submission);

      // Check if empty value is excluded.
      if ($webform_element->isEmptyExcluded($element, $options) && !$webform_element->getValue($element, $webform_submission, $options)) {
        continue;
      }

      $title = $element['#admin_title'] ?: $element['#title'] ?: '(' . $key . ')';

      // Note: Not displaying an empty message since empty values just render
      // an empty table cell.
      /* @internal OS2Forms changes start */
      switch ($element['#type']) {
        case 'container':
        case 'webform_table_row':
          // Prevent row rendering.
          continue 2;

        case 'webform_wizard_page':
          $submissionDisplay = $webform_element->getElementProperty($element, 'format');
          $title = $submissionDisplay === 'header' ? '<h2>' . $title . '</h2>' : $title;
          $html = [
            '#plain_text' => '',
          ];
          break;

        case 'fieldset':
        case 'webform_table':
          $submissionDisplay = $webform_element->getElementProperty($element, 'format');
          $title = $submissionDisplay === 'header' ? '<h3>' . $title . '</h3>' : $title;
          $html = [
            '#plain_text' => '',
          ];
          break;

        case 'webform_markup':
          $html = $webform_element->buildHtml($element, $webform_submission, $options);
          $title = '';
          break;

        default:
          $html = $webform_element->formatHtml($element, $webform_submission, $options);
      }
      /* @internal OS2Forms changes end */
      $rows[$key] = [
        ['header' => TRUE, 'data' => ['#markup' => $title]],
        ['data' => (is_string($html)) ? ['#markup' => $html] : $html],
      ];
    }

    return [
      '#type' => 'table',
      '#rows' => $rows,
      '#attributes' => [
        'class' => ['webform-submission-table'],
      ],
    ];
  }

}
