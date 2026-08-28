<?php

use Drupal\webform\Entity\WebformSubmission;
use Symfony\Component\Yaml\Yaml;

require_once __DIR__ . '/base.php';

final class Script extends AbstractScript {
  protected function run(): void {
    if (!$this->confirm('Really import submissions?')) {
      return;
    }

    $filenames = glob($this->dataDir . '/submissions/*.submissions.json');

    $webformStorage = \Drupal::entityTypeManager()->getStorage('webform');
    $submissionStorage = \Drupal::entityTypeManager()->getStorage('webform_submission');

    foreach ($filenames as $filename) {
      $webformId = basename($filename, '.submissions.json');
      /** @var ?\Drupal\webform\WebformInterface $webform */
      $webform = $webformStorage->load($webformId);
      if (!$webform) {
        continue;
      }

      $this->writeln('Webform %s', $webform->label());

      /** @var \Drupal\webform\WebformSubmissionInterface[] $submissions */
      $submissions = $submissionStorage->loadByProperties(['webform_id' => $webform->id()]);
      foreach ($submissions as $submission) {
        $submission->delete();
      }

      $data = (array) Yaml::parse(file_get_contents($filename));
      foreach ($data as $sid => $datum) {
        $this->writeln('%s: %s', $webform->id(), $sid);
        $submission = WebformSubmission::create([
          'webform_id' => $webformId,
          'sid' => $sid,
        ]);
        $submission->setData($datum);
        $submission->save();

        $this->writeln('Submission created: %s (%d)', $submission->label(), $submission->id());
      }
    }
  }
}

new Script();
