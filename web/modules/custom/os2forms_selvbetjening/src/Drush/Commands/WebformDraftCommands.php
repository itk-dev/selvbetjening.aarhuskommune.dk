<?php

namespace Drupal\os2forms_selvbetjening\Drush\Commands;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\WebformInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform draft commands.
 */
final class WebformDraftCommands extends DrushCommands {
  use StringTranslationTrait;

  /**
   * Constructs a WebformDraftCommands object.
   */
  public function __construct(
    private readonly EntityTypeManager $entityTypeManager,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * List all webforms with drafts enabled.
   */
  #[CLI\Command(name: 'os2forms-selvbetjening:draft-webforms')]
  #[CLI\Option(name: 'list-os2forms-session-settings', description: 'List os2forms authentication settings')]
  #[CLI\Option(name: 'indicate-session-enabled', description: 'Indicate whether sessions are enabled or not')]
  #[CLI\Option(name: 'group-by-session-type', description: 'Group results by session type')]
  #[CLI\Usage(name: 'os2forms_selvbetjening:draft-webforms', description: 'Gets webforms with drafts enabled.')]
  public function draftWebforms(
    array $options = [
      'list-os2forms-session-settings' => FALSE,
      'indicate-session-enabled' => FALSE,
      'group-by-session-type' => FALSE,
    ],
  ) {

    $draftWebforms = $this->getWebformsWithDraftsEnabled();

    $addSessionSettings = $options['list-os2forms-session-settings'];
    $indicateSessionEnabled = $options['indicate-session-enabled'];
    $groupBySessionType = $options['group-by-session-type'];

    if (empty($draftWebforms)) {
      $this->output()->writeln('No webforms found with drafts enabled.');
    }
    else {
      $this->output()->writeln($this->formatPlural(count($draftWebforms), 'One webform has drafts enabled:', '@count webforms has drafts enabled:'));

      if ($groupBySessionType) {

        $groupedDraftWebforms = [];

        foreach ($draftWebforms as $webform) {
          $sessionSettings = $webform->getThirdPartySetting('os2forms', 'os2forms_nemid');
          $type = !empty($sessionSettings['session_type']) ? $sessionSettings['session_type'] : 'default';
          $groupedDraftWebforms[$type][] = $webform;
        }

        foreach ($groupedDraftWebforms as $type => $webforms) {
          $this->output()->writeln("\n--- Session Type: $type ---");
          foreach ($webforms as $webform) {
            $this->output()->writeln($this->formatRow($webform, $addSessionSettings, $indicateSessionEnabled));
          }
        }
      }
      else {
        foreach ($draftWebforms as $webform) {
          $this->output()->writeln($this->formatRow($webform, $addSessionSettings, $indicateSessionEnabled));
        }
      }
    }
  }

  /**
   * Get webforms with drafts enabled.
   */
  private function getWebformsWithDraftsEnabled() {
    try {
      $webforms = $this->entityTypeManager->getStorage('webform')->loadMultiple();
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $this->logger()->error('Failed to load webforms: @error', ['@error' => $e->getMessage()]);
      throw $e;
    }

    $draftWebforms = [];

    foreach ($webforms as $webform) {
      /** @var \Drupal\webform\WebformInterface $webform */
      $settings = $webform->getSettings();

      // Check if draft mode is enabled ('authenticated' or 'all').
      // Settings can be: 'none', 'authenticated', or 'all'.
      if (isset($settings['draft']) && $settings['draft'] !== 'none') {
        $draftWebforms[] = $webform;
      }
    }

    return $draftWebforms;
  }

  /**
   * Formats a single output row.
   */
  private function formatRow(WebformInterface $webform, bool $addSessionSettings, bool $indicateSessionEnabled): string {
    $sessionSettings = $webform->getThirdPartySetting('os2forms', 'os2forms_nemid');

    if ($addSessionSettings) {
      $line = $webform->id() . ': ' . json_encode($sessionSettings);
    }
    else {
      $line = $webform->id();
    }

    if ($indicateSessionEnabled) {
      $isEnabled = ($sessionSettings['nemlogin_auto_redirect'] ?? 0) === 1;
      $line = ($isEnabled ? '✅ ' : '❌ ') . $line;
    }

    return $line;
  }

}
