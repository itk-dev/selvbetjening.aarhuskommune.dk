<?php

require_once __DIR__ . '/base.php';

final class Script extends AbstractScript {
  protected function run(): void {
    $outputDir = $this->dataDir . '/submissions';

    /** @var \Drupal\webform\WebformInterface[] $webforms */
    $webforms = \Drupal::entityTypeManager()->getStorage('webform')->loadMultiple($this->webformIds);
    $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
    foreach ($webforms as $webform) {
      $this->writeln('Webform %s', $webform->label());

      $submissionData = [];
      /** @var \Drupal\webform\WebformSubmissionInterface[] $submissions */
      $submissions = $submission_storage->loadByProperties(['webform_id' => $webform->id()]);
      foreach ($submissions as $submission) {
        $this->writeln('Exporting %s', $submission->label());

        $submissionData[$submission->id()] = $submission->getData();
      }

      file_put_contents($outputDir . '/' . $webform->id() . '.submissions.json', json_encode($submissionData, JSON_PRETTY_PRINT));

      if (0 === count($submissions)) {
        $this->writeln('No submissions exported');
      }
      elseif (1 === count($submissions)) {
        $this->writeln('One submissions exported');
      }
      else {
        $this->writeln('%d submissions exported', count($submissions));
      }
    }
  }
}

new Script();
