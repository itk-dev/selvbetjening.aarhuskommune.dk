<?php

namespace Drupal\itkdev_example_forms\Fixture;

/**
 * User affiliation term fixture.
 */
final class UserAffiliationTermFixture extends AbstractTaxonomyTermFixture {
  /**
   * {@inheritdoc}
   */
  protected static string $vocabularyId = 'user_affiliation';

  /**
   * {@inheritdoc}
   */
  protected static bool $forceTermIds = TRUE;

  /**
   * {@inheritdoc}
   */
  protected static array $terms = [
    1 => 'ITK Development',
    2 => 'Another department',
  ];

}
