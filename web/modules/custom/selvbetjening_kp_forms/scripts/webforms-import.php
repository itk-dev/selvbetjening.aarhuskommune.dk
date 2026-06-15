<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

use Symfony\Component\Process\Process;

final class Script extends AbstractScript {
  private function drush(string $cmd, string ...$arguments): void {
    $command = ['php', 'vendor/bin/drush', $cmd, ...$arguments];
    $process = new Process($command, dirname(DRUPAL_ROOT));
    $process->setTty(TRUE);
    $process->start();

    foreach ($process as $type => $data) {
      fwrite($type === $process::OUT ? STDOUT : STDERR, $data);
    }
  }

  protected function run(): void {
    if (!$this->confirm('Really import webforms?')) {
      return;
    }

    $this->drush('--yes', 'config:import', '--partial', '--source=' . $this->dataDir . '/webforms');
  }
}

new Script();
