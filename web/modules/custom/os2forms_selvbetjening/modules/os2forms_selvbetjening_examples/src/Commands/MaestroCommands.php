<?php

namespace Drupal\os2forms_selvbetjening_examples\Commands;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * A drush command file.
 */
class MaestroCommands extends DrushCommands {
  /**
   * The task storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private EntityStorageInterface $taskStorage;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->taskStorage = $entityTypeManager->getStorage('maestro_queue');
  }

  /**
   * Manage Maestro tasks.
   *
   * @param string $action
   *   The command to run; "list" or "purge".
   * @param array $options
   *   The options.
   *
   * @command os2forms-selvbetjening-examples:maestro:task
   * @usage os2forms-selvbetjening-examples:maestro:task cmd
   *
   * @default $options []
   */
  public function task($action = 'list', array $options = []) {
    switch ($action) {
      case 'list':
        $tasks = $this->loadTasks();
        foreach ($tasks as $task) {
          $this->output()->writeln(json_encode($task->toArray(), JSON_PRETTY_PRINT));
        }
        break;

      case 'purge':
        if ($options['yes']) {
          $tasks = $this->loadTasks();
          foreach ($tasks as $task) {
            $task->delete();
          }
        }
        break;

      default:
        break;
    }
  }

  /**
   * Load tasks.
   *
   * @return array|\Drupal\maestro\Entity\MaestroQueue[]
   *   The tasks.
   */
  private function loadTasks(): array {
    /** @var \Drupal\maestro\Entity\MaestroQueue[] $tasks */
    $tasks = $this->taskStorage->loadMultiple();

    return $tasks;
  }

}
