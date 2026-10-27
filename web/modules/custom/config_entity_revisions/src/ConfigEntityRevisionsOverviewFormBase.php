<?php

namespace Drupal\config_entity_revisions;

use Drupal\Component\Utility\Xss;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\diff\DiffLayoutManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller to make library functions available to various consumers.
 */
abstract class ConfigEntityRevisionsOverviewFormBase extends FormBase implements ConfigEntityRevisionsOverviewFormBaseInterface {

  use StringTranslationTrait;

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
    protected DateFormatterInterface $dateFormatter,
    protected RendererInterface $renderer,
    protected ImmutableConfig $config,
    protected ModuleHandler $moduleHandler,
    protected EntityTypeManager $entityTypeManager,
    protected ?ModerationInformationInterface $moderationInformation,
    protected ?DiffLayoutManager $diffLayoutManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    $diffLayoutManager = $container->has('plugin.manager.diff.layout') ?
      $container->get('plugin.manager.diff.layout') : NULL;

    $contentModerationInformation = $container->has('content_moderation.moderation_information') ?
      $container->get('content_moderation.moderation_information') : NULL;

    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('config.factory')->get('diff.settings'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $contentModerationInformation,
      $diffLayoutManager
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'config_entity_revisions_overview';
  }

  /**
   * Set column attributes and return config array.
   *
   * @param string $name
   *   Name attribute.
   * @param string $return_val
   *   Return value attribute.
   * @param string $default_val
   *   Default value attribute.
   *
   * @return array
   *   Configuration array.
   */
  protected function buildSelectColumn($name, $return_val, $default_val) {
    return [
      '#type' => 'radio',
      '#title_display' => 'invisible',
      '#name' => $name,
      '#return_value' => $return_val,
      '#value' => FALSE,
      '#default_value' => $default_val,
    ];
  }

  /**
   * Generates an overview table of older revisions of a config entity.
   *
   * @param array $form
   *   A form being built.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $configEntity
   *   A configuration entity.
   *
   * @return array
   *   An array as expected by \Drupal\Core\Render\RendererInterface::render().
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function buildForm(array $form, FormStateInterface $form_state, ConfigEntityInterface $configEntity = NULL) {
    // Disable for now, having seen
    // https://www.drupal.org/project/diff/issues/2452523.
    $haveDiff = $this->moduleHandler->moduleExists('diff') && FALSE;
    $haveModeration = $this->moduleHandler->moduleExists('content_moderation');

    $configEntityType = $configEntity->getEntityType()->id();

    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $contentEntityStorage */
    $contentEntityStorage = $configEntity->contentEntityStorage();

    $account = $this->currentUser();

    /** @var \Drupal\config_entity_revisions\Entity\ConfigEntityRevisions $revisionsEntity */
    $revisionsEntity = $configEntity->getContentEntity();

    if (!$revisionsEntity) {
      $form['#title'] = $this->t('No revisions for %title', ['%title' => $configEntity->label()]);

      $form['message'] = [
        '#theme' => 'markup',
        '#markup' => "No revisions have been created for this configuration entity yet.",
      ];

      return $form;
    }

    if ($haveModeration) {
      $moderation_enabled = $this->moderationInformation->shouldModerateEntitiesOfBundle($revisionsEntity->getEntityType(), $revisionsEntity->bundle());
    }
    else {
      $moderation_enabled = FALSE;
    }

    $revert_permission = (($account->hasPermission("administer $configEntityType revisions") || $account->hasPermission("revert $configEntityType revisions") || $account->hasPermission('revert all revisions') || $account->hasPermission('administer nodes')) && $configEntity->access('update'));
    $delete_permission = (($account->hasPermission("administer $configEntityType revisions") || $account->hasPermission("delete $configEntityType revisions") || $account->hasPermission('delete all revisions') || $account->hasPermission('administer nodes')) && $configEntity->access('delete'));

    $pagerLimit = 10;

    $query = $contentEntityStorage->getQuery()
      ->condition($revisionsEntity->getEntityType()->getKey('id'),
        $revisionsEntity->id())
      ->pager($pagerLimit)
      ->allRevisions()
      ->sort($revisionsEntity->getEntityType()->getKey('revision'), 'DESC')
      // Access to the content has already been verified. Disable query-level
      // access checking so that revisions for unpublished content still
      // appear.
      ->accessCheck(FALSE)
      ->execute();
    $vids = array_keys($query);

    $revision_count = count($vids);

    $table_header = [
      'revision' => $this->t('Revision'),
    ];

    if ($configEntity->hasOwnContent()) {
      $table_header['submissions'] = $this->t('Submissions');
    }

    // Allow comparisons only if there are 2 or more revisions.
    if ($haveDiff && $revision_count > 1) {
      $table_header += [
        'select_column_one' => '',
        'select_column_two' => '',
      ];
    }

    if ($moderation_enabled) {
      $table_header['status'] = $this->t('Status');
    }

    $table_header['operations'] = $this->t('Operations');

    $form['revisions_table'] = [
      '#type' => 'table',
      '#header' => $table_header,
    ];

    if ($haveDiff) {
      $form['revisions_table'] += [
        '#attributes' => [
          'class' => [
            'diff-revisions',
          ],
        ],
        '#attached' => [
          'library' => [
            'diff/diff.general',
          ],
          'drupalSettings' => [
            'diffRevisionRadios' => $this->config->get('general_settings.radio_behavior'),
          ],
        ],
      ];
    }

    $form['entity_type'] = [
      '#type' => 'hidden',
      '#value' => $configEntityType,
    ];
    $form['entity_id'] = [
      '#type' => 'hidden',
      '#value' => $configEntity->id(),
    ];

    // We need to handle there being no published revisions, in which case
    // every row should have a 'Publish' button. In that situation, we can't
    // simply look at whether a revision is before or after the 'default'
    // revision.
    $published_revision_id = $revisionsEntity->isPublished() ? $revisionsEntity->getRevisionId() : 0;

    foreach ($vids as $vid) {
      /** @var \Drupal\config_entity_revisions\Entity\ConfigEntityRevisions $revision */
      $revision = $contentEntityStorage->loadRevision($vid);
      $configRevision = $this->entityTypeManager->getStorage($configEntityType)
        ->loadRevision($vid);

      $username = [
        '#theme' => 'username',
        '#account' => $revision->getRevisionUser(),
      ];

      // Use revision link for linking to revisions that are not active.
      $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
      $link = $configRevision->revisionLink($date);

      $column = [
        'data' => [
          '#type' => 'inline_template',
          '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
          '#context' => [
            'date' => $link,
            'username' => $this->renderer->renderInIsolation($username),
            'message' => [
              '#markup' => $revision->getRevisionLogMessage(),
              '#allowed_tags' => Xss::getHtmlTagList(),
            ],
          ],
        ],
      ];

      $this->renderer->addCacheableDependency($column['data'], $username);
      $row = ['description' => $column];

      if ($configEntity->hasOwnContent()) {
        $count = $configEntity->contentEntityCount($vid);
        $pluralised = $this->stringTranslation
          ->formatPlural($count, '1 submission', '@count submissions');
        $row['submissions'] = [
          '#markup' => $count ? $pluralised : $this->t('No submissions'),
        ];
      }

      // Allow comparisons only if there are 2 or more revisions.
      if ($haveDiff && $revision_count > 1) {
        $row += [
          'select_column_one' => $this->buildSelectColumn('radios_left', $vid, FALSE),
          'select_column_two' => $this->buildSelectColumn('radios_right', $vid, $vid),
        ];
      }

      if ($moderation_enabled) {
        $workflow = $this->moderationInfo->getWorkflowForEntity($configEntity);
        $current_state = $revision->moderation_state->value;
        $row['status'] = [
          '#markup' => $workflow->getTypePlugin()->getState($current_state)->label(),
        ];
      }

      if ($revision->isDefaultRevision() && $revision->isPublished()) {
        $row += [
          'data' => [
            '#prefix' => '<em>',
            '#markup' => $this->t('Current revision'),
            '#suffix' => '</em>',
          ],
          '#attributes' => [
            'class' => ['revision-current'],
          ],
        ];
      }
      else {
        $links = [];
        if ($revert_permission) {
          $links['revert'] = [
            'title' => $vid < $published_revision_id ? $this->t('Revert') : $this->t('Publish'),
            'url' => Url::fromRoute("entity.{$configEntityType}.revision_revert_confirm", [
              'config_entity' => $configEntity->id(),
              "revision_id" => $vid,
            ]),
          ];
        }

        if ($delete_permission && $revision_count > 1 && $configRevision->mayDeleteRevision()) {
          $links['delete'] = [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute("entity.{$configEntityType}.revision_delete_confirm", [
              'config_entity' => $configEntity->id(),
              'revision_id' => $vid,
            ]),
          ];
        }

        $row['data'] = [
          '#type' => 'operations',
          '#links' => $links,
        ];
      }

      $form['revisions_table'][$vid] = $row;
    }

    // Allow comparisons only if there are 2 or more revisions.
    if ($haveDiff && $revision_count > 1) {
      $form['submit'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => $this->t('Compare selected revisions'),
        '#attributes' => [
          'class' => [
            'diff-button',
          ],
        ],
      ];
    }

    $form['pager'] = ['#type' => 'pager'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();

    if (count($form_state->getValue('revisions_table')) <= 1) {
      $form_state->setErrorByName('revisions_table', $this->t('Multiple revisions are needed for comparison.'));
    }
    elseif (!isset($input['radios_left']) || !isset($input['radios_right'])) {
      $form_state->setErrorByName('revisions_table', $this->t('Select two revisions to compare.'));
    }
    elseif ($input['radios_left'] == $input['radios_right']) {
      $form_state->setErrorByName('revisions_table', $this->t('Select different revisions to compare.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $vid_left = $input['radios_left'];
    $vid_right = $input['radios_right'];
    $entity_type = $input['entity_type'];
    $entity_id = $input['entity_id'];

    // Always place the older revision on the left side of the comparison
    // and the newer revision on the right side (however revisions can be
    // compared both ways if we manually change the order of the parameters).
    if ($vid_left > $vid_right) {
      $aux = $vid_left;
      $vid_left = $vid_right;
      $vid_right = $aux;
    }

    // Builds the redirect Url.
    $redirect_url = Url::fromRoute(
      "diff.config_entity_revisions_diff",
      [
        'config_entity_type' => $entity_type,
        'config_entity_id' => $entity_id,
        'left_revision' => $vid_left,
        'right_revision' => $vid_right,
        'filter' => $this->diffLayoutManager->getDefaultLayout(),
      ]
    );
    $form_state->setRedirectUrl($redirect_url);
  }

}
