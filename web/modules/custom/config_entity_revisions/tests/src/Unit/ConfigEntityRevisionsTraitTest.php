<?php

namespace Drupal\Tests\config_entity_revisions\Unit;

use Drupal\config_entity_revisions\ConfigEntityRevisionsConfigTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Test traits for ConfigEntityRevisions.
 *
 * @group config_entity_revisions
 */
class ConfigEntityRevisionsTraitTest extends UnitTestCase {

  /**
   * Test that we can set and get a revision ID using the trait.
   *
   * @test
   */
  public function canSetAndGetRevisionId() {
    $instance = new TraitTest();
    $instance->setRevisionId(704);

    $this->assertEquals(704, $instance->getRevisionId());
  }

}

/**
 * Trait test class.
 */
class TraitTest {
  use ConfigEntityRevisionsConfigTrait;

}
