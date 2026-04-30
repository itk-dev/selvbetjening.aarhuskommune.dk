<?php

namespace Drupal\os2forms_selvbetjening\Drush\Commands;

use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Plugin\AdvancedQueue\Backend\SupportsLoadingJobsInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Serialization\Yaml;
use Drush\Attributes as CLI;
use Drush\Commands\config\ConfigCommands;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * A Drush commandfile.
 */
final class AdvancedQueueCommands extends DrushCommands {
  use AutowireTrait;

  /**
   * The queue storage.
   */
  private EntityStorageInterface $queueStorage;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    private readonly Connection $connection,
  ) {
    $this->queueStorage = $entityTypeManager->getStorage('advancedqueue_queue');
  }

  private const string COMMAND_LIST_JOBS = 'os2forms-selvbetjening:advancedqueue:queue:list:jobs';
  private const string COMMAND_SET_PAYLOAD_VALUE = 'os2forms-selvbetjening:advancedqueue:job:set-payload-value';

  /**
   * List jobs command.
   *
   * @phpstan-param array<string, mixed> $options
   *   The command options.
   */
  #[CLI\Command(name: self::COMMAND_LIST_JOBS, aliases: ['advancedqueue:queue:list:jobs'])]
  #[CLI\Argument(name: 'queue_id', description: 'The queue ID.')]
  #[CLI\Argument(name: 'job_id', description: 'Job ID')]
  #[CLI\Option(name: 'show-payload', description: 'Show payload')]
  #[CLI\Option(name: 'search', description: 'Search for a value, by path or by a JSON object')]
  #[CLI\Option(name: 'limit', description: 'Maximum number of jobs to return (at most 100)')]
  #[CLI\Usage(name: self::COMMAND_LIST_JOBS . ' my_queue', description: 'Show jobs in "my_queue" queue.')]
  #[CLI\Usage(name: self::COMMAND_LIST_JOBS . ' my_queue 87', description: 'Show job with ID 87 in "my_queue" queue.')]
  #[CLI\Usage(name: self::COMMAND_LIST_JOBS . ' my_queue --show-payload', description: 'Show job payload.')]
  #[CLI\Usage(name: self::COMMAND_LIST_JOBS . ' my_queue --search horse', description: 'List jobs where "horse" is a value in the payload.')]
  #[CLI\Usage(name: self::COMMAND_LIST_JOBS . ' my_queue --search handler.id=87', description: 'List jobs where payload.handler.id has the (string) value "87".')]
  #[CLI\Usage(name: self::COMMAND_LIST_JOBS . ' my_queue --search \'{"submissionId": "87"}\'', description: 'List jobs where payload contains the specified JSON object.')]
  public function listJobs(
    string $queue_id,
    ?string $job_id = NULL,
    $options = [
      'show-payload' => FALSE,
      'search' => NULL,
      'limit' => 1,
    ],
  ) {
    [, $backend] = $this->loadQueueAndBackend($queue_id);

    // Build the query by hand to use JSON functions (cf.
    // https://www.drupal.org/project/drupal/issues/3378275)
    $query = 'SELECT job_id FROM {advancedqueue} WHERE queue_id = :queue_id';
    $params = [
      ':queue_id' => $queue_id,
    ];
    if ($job_id) {
      $query .= 'AND job_id = :job_id';
      $params[':job_id'] = $job_id;
    }
    if ($search = ($options['search'] ?? NULL)) {
      $obj = json_decode($search);
      if ($obj && is_object($obj)) {
        // Search by JSON object, e.g
        // --search '{"submissionId": "1"}'
        // https://mariadb.com/docs/server/reference/sql-functions/special-functions/json-functions/json_contains
        $query .= ' AND JSON_CONTAINS(payload, JSON_EXTRACT(:value, \'$\'))';
        $params[':value'] = json_encode($obj);
      }
      elseif (preg_match('/^(?P<path>[^=]+)=(?P<value>.+)/', $search, $matches)) {
        // Search by path and value, e.g
        // --search handlerSettings.handler_id=fordelingskomponent_sf2900_1
        // https://mariadb.com/docs/server/reference/sql-functions/special-functions/json-functions/json_extract
        $query .= ' AND JSON_EXTRACT(payload, :path) = :value';
        $params[':path'] = '$.' . $matches['path'];
        $params[':value'] = $matches['value'];
      }
      else {
        // Search by value, e.g
        // --search fordelingskomponent_sf2900_1
        // https://mariadb.com/docs/server/reference/sql-functions/special-functions/json-functions/json_search
        $query .= ' AND JSON_SEARCH(payload, \'one\', :value) IS NOT NULL';
        $params[':value'] = $search;
      }
    }
    // Clamp limit between 0 and 100 (both inclusive).
    $limit = max(0, min(100, (int) $options['limit']));
    // @todo Can we pass limit as a parameter?
    $query .= ' ORDER BY job_id DESC LIMIT ' . $limit;

    $jobIds = $this->connection->query($query, $params)->fetchCol();

    if (empty($jobIds)) {
      $this->io()->info('No jobs found.');
      return self::EXIT_SUCCESS;
    }

    /** @var \Drupal\advancedqueue\Job[] $jobs */
    $jobs = array_map(fn(string $id) => $backend->loadJob($id), $jobIds);

    foreach ($jobs as $job) {
      $this->io()->definitionList(
        ['ID' => $job->getId()],
        ['State' => $job->getState()],
        ['Message' => $job->getMessage()],
        ['Processed time' => $this->formatDateTime($job->getProcessedTime())],
        ['Available time' => $this->formatDateTime($job->getAvailableTime())],
        ['Expires time' => $this->formatDateTime($job->getExpiresTime())],
        ['Num retries' => $job->getNumRetries()],
      );
      if ($options['show-payload']) {
        $this->io()->section('Payload');
        $this->io()->writeln(Yaml::encode($job->getPayload()));
      }
    }

    return self::EXIT_SUCCESS;
  }

  /**
   * Set job payload value.
   *
   * @phpstan-param array<string, mixed> $options
   *   The command options.
   */
  #[CLI\Command(name: self::COMMAND_SET_PAYLOAD_VALUE)]
  #[CLI\Argument(name: 'queue_id', description: 'The queue ID.')]
  #[CLI\Argument(name: 'job_id', description: 'Job ID')]
  #[CLI\Option(name: 'diff', description: 'Show payload diff')]
  #[CLI\Option(name: 'set', description: 'Set string value')]
  #[CLI\Option(name: 'set-int', description: 'Set integer value')]
  #[CLI\Option(name: 'unset', description: 'Unset value')]
  #[CLI\Usage(name: self::COMMAND_SET_PAYLOAD_VALUE . ' my_queue 87 --set person.name=James', description: 'Set string value')]
  #[CLI\Usage(name: self::COMMAND_SET_PAYLOAD_VALUE . ' my_queue 87 --set-int person.age=61', description: 'Set integer value')]
  #[CLI\Usage(name: self::COMMAND_SET_PAYLOAD_VALUE . ' my_queue 87 --unset person.address', description: 'Unset value')]
  #[CLI\Usage(name: self::COMMAND_SET_PAYLOAD_VALUE . ' my_queue 87 --set person.name=James --unset person.address --diff', description: 'Perform multiple operations and preview changes')]
  public function setJobPayloadValue(
    string $queue_id,
    string $job_id,
    $options = [
      'diff' => FALSE,
      'set' => [],
      'set-int' => [],
      'unset' => [],
    ],
  ) {
    [, $backend] = $this->loadQueueAndBackend($queue_id);
    try {
      $updateJob = new \ReflectionMethod($backend, 'updateJob');
    }
    catch (\ReflectionException $e) {
      throw new RuntimeException(dt('Cannot update job in queue (%message)', ['%message' => $e->getMessage()]));
    }

    /** @var \Drupal\advancedqueue\Job $job */
    $job = $backend->loadJob($job_id);
    $payload = $job->getPayload();

    foreach ($options['set'] as $spec) {
      [$path, $value] = explode('=', $spec, 2);
      NestedArray::setValue($payload, explode('.', $path), $value);
    }
    foreach ($options['set-int'] as $spec) {
      [$path, $value] = explode('=', $spec, 2);
      NestedArray::setValue($payload, explode('.', $path), (int) $value);
    }
    foreach ($options['unset'] as $path) {
      NestedArray::unsetValue($payload, explode('.', $path));
    }

    $diff = $this->renderDiff($job->getPayload(), $payload);
    if (empty($diff)) {
      $this->io()->info('No changes to apply');

      return self::EXIT_SUCCESS;
    }

    $question = dt('Apply the payload changes?');
    if ($options['diff']) {
      $this->io()->writeln($diff);
      $question = dt('Apply the listed payload changes?');
    }

    if (!$this->io()->confirm($question)) {
      throw new UserAbortException();
    }

    $job->setPayload($payload);
    try {
      $updateJob->invoke($backend, $job);
    }
    catch (\ReflectionException $e) {
      throw new RuntimeException(dt('Cannot update job: %message', ['%message' => $e->getMessage()]));
    }

    return self::EXIT_SUCCESS;
  }

  /**
   * Load a queue and its backend.
   *
   * @return array{QueueInterface, BackendInterface&SupportsLoadingJobsInterface}
   *   The queue and its backend.
   */
  private function loadQueueAndBackend(string $queueId): array {
    $queue = $this->queueStorage->load($queueId);
    if (NULL === $queue) {
      throw new RuntimeException(dt('Cannot load queue %id.', ['%id' => $queueId]));
    }
    assert($queue instanceof Queue);

    $backend = $queue->getBackend();
    if (!$backend instanceof SupportsLoadingJobsInterface) {
      throw new RuntimeException(dt('Backend (%backend_class) for %queue does not support loading jobs.', [
        '%backend_class' => $backend::class,
        '%queue' => $queue->label(),
      ]));
    }

    return [$queue, $backend];
  }

  /**
   * Format a timestamp.
   */
  private function formatDateTime(int|string $timestamp): ?string {
    return $timestamp ? DrupalDateTime::createFromTimestamp((int) $timestamp)->format(DrupalDateTime::FORMAT) : NULL;
  }

  /**
   * Lifted from Drush's config commands.
   */
  private function renderDiff(array $a, array $b): string {
    $createConfigStorage = function (array $values): StorageInterface {
      $storage = new MemoryStorage();

      foreach ($values as $key => $value) {
        $storage->write($key, (array) $value);
      }

      return $storage;
    };

    return ConfigCommands::getDiff(
      $createConfigStorage([__FUNCTION__ => $a]),
      $createConfigStorage([__FUNCTION__ => $b]),
      $this->output()
    );
  }

}
