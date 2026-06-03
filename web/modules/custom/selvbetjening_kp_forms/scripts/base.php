<?php

abstract class AbstractScript {
  protected array $webformIds = [
    'ansoegning_om_helbredstillaeg_sp',
    'sp242_xsd',
    'erklaering_fra_optiker_sp246_000',
    'ansoegning_om_personligt_tillaeg',
    'helbredstillaeg_refundering_af_u',
  ];

  protected string $handlerId = 'fordelingskomponent_sf2900';

  protected string $dataDir = __DIR__ . '/../data';

  protected function writeln(string $format, mixed ...$args): void {
    echo call_user_func_array('sprintf', [$format, ...$args]), PHP_EOL;
  }

  public function __construct() {
    $this->run();
  }

  protected function confirm(string $question, mixed ...$args): bool {
    $question = call_user_func_array('sprintf', [$question, ...$args]);

    $reply = readline($question . ' (y/N): ');

    return (bool) preg_match('/^[y]/i', $reply);
  }

  abstract protected function run(): void;
}
