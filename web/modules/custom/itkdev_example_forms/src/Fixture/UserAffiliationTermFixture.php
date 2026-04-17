<?php

namespace Drupal\itkdev_example_forms\Fixture;

/**
 * User affiliation term fixture.
 */
final class UserAffiliationTermFixture extends AbstractTaxonomyTermFixture {
  public const ITK_DEVELOPMENT = 1;
  public const ANOTHER_DEPARTMENT = 2;

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
    self::ITK_DEVELOPMENT => 'ITK Development',
    self::ANOTHER_DEPARTMENT => 'Another department',
  ];

}
