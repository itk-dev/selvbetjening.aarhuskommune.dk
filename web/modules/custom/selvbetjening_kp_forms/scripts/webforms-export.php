<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class Script extends AbstractScript {
  private function drush(string $cmd, string ...$arguments): string {
    $command = ['php', 'vendor/bin/drush', $cmd, ...$arguments];
    $process = new Process($command, dirname(DRUPAL_ROOT));
    $process->run();

    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }

    return $process->getOutput();
  }

  protected function run(): void {
    $outputDir = __DIR__ . '/../data/webforms';

    foreach ($this->webformIds as $webformId) {
      $configKey = 'webform.webform.' . $webformId;
      $outputPath = $outputDir . '/' . $configKey . '.yml';

      $this->writeln('Exporting %s (%s -> %s)', $webformId, $configKey, $outputPath);

      $config = $this->drush('config:get', $configKey);

      file_put_contents($outputPath, $config);
    }
  }
}

new Script();
