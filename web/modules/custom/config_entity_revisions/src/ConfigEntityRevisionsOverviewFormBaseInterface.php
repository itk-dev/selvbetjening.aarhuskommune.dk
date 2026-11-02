<?php

namespace Drupal\config_entity_revisions;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\diff\DiffLayoutManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller to make library functions available to various consumers.
 */
interface ConfigEntityRevisionsOverviewFormBaseInterface extends FormInterface, ContainerInjectionInterface {

  /**
   * Constructs a ConfigEntityRevisionsController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The configuration service.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager service.
   * @param Drupal\content_moderation\ModerationInformation $moderationInformation
   *   The content moderation information service.
   * @param \Drupal\diff\DiffLayoutManager $diffLayoutManager
   *   The diff layout manager service.
   */
  public function __construct(
    DateFormatterInterface $dateFormatter,
    RendererInterface $renderer,
    ImmutableConfig $config,
    ModuleHandler $moduleHandler,
    EntityTypeManager $entityTypeManager,
    ModerationInformationInterface $moderationInformation,
    ?DiffLayoutManager $diffLayoutManager
  );

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container);

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId();

  /**
   * Generates an overview table of older revisions of a config entity.
   *
   * @param array $form
   *   A form being built.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $config_entity
   *   A configuration entity.
   *
   * @return array
   *   An array as expected by \Drupal\Core\Render\RendererInterface::render().
   */
  public function buildForm(array $form, FormStateInterface $form_state, ConfigEntityInterface $config_entity = NULL);

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state);

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state);

}
