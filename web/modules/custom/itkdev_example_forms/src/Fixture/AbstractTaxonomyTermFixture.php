<?php

namespace Drupal\itkdev_example_forms\Fixture;

use Drupal\content_fixtures\Fixture\AbstractFixture;
use Drupal\content_fixtures\Fixture\FixtureGroupInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Abstract taxonomy term fixture.
 */
abstract class AbstractTaxonomyTermFixture extends AbstractFixture implements FixtureGroupInterface {
  /**
   * The vocabulary id.
   *
   * @var string
   */
  protected static string $vocabularyId;

  /**
   * The terms.
   *
   * Each item must be a term name or term name => [child term names], e.g.
   *
   * [
   *   'test',
   *   'science' => [
   *     'math',
   *     'computer science',
   *   ],
   *   'books',
   * ]
   *
   * @var array
   */
  protected static array $terms;

  /**
   * Force term IDs.
   *
   * If set, you must specify a unique positive integer ID for all terms:
   *
   * [
   *   1 => 'test',
   *   'science' => [
   *     '#tid' => 2,
   *     3 => 'math',
   *     4 => 'computer science',
   *   ],
   *   7 => 'books',
   * ]
   *
   * @var bool
   */
  protected static bool $forceTermIds = FALSE;

  /**
   * Constructor.
   */
  public function __construct() {
    if (empty(static::$vocabularyId)) {
      throw new \RuntimeException(sprintf('Vocabulary id not defined in %s', static::class));
    }
    if (empty(static::$terms)) {
      throw new \RuntimeException(sprintf('No terms defined in %s', static::class));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createTerms(static::$terms);
  }

  /**
   * Create terms.
   *
   * @param array $items
   *   The items.
   * @param \Drupal\taxonomy\Entity\Term|null $parent
   *   The optional term parent.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createTerms(array $items, ?Term $parent = NULL) {
    $weight = 0;
    foreach ($items as $name => $value) {
      if (is_array($value)) {
        $termId = static::$forceTermIds ? ($value['#tid'] ?? NULL) : NULL;
        unset($value['#tid']);
        $this->checkTermId($termId, $name, $value);
        $term = $this->createTerm($name, $weight, $parent, $termId);
        $this->createTerms($value, $term);
      }
      else {
        $termId = static::$forceTermIds ? $name : NULL;
        $this->checkTermId($termId, $name, $value);
        $this->createTerm($value ?: $name, $weight, $parent, $termId);
      }
      $weight++;
    }
  }

  /**
   * Check that a term ID is valid.
   */
  private function checkTermId(mixed $termId, string $name, mixed $value) {
    if (static::$forceTermIds) {
      if (NULL === $termId) {
        throw new \RuntimeException(sprintf('Missing term ID for "%s"', $name));
      }
      if (!is_int($termId) || $termId < 1) {
        throw new \RuntimeException(sprintf('Invalid term ID %s for "%s"; it must be a positive integer', var_export($termId, TRUE),
          $name));
      }
    }
  }

  /**
   * Create a term.
   *
   * @param string $name
   *   The term name.
   * @param int $weight
   *   The term weight.
   * @param \Drupal\taxonomy\Entity\Term|null $parent
   *   The optional term parent.
   * @param int|null $termId
   *   The forced term ID.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   The term.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createTerm(string $name, int $weight, ?Term $parent = NULL, ?int $termId = NULL) {
    $values = [
      'vid' => static::$vocabularyId,
      'weight' => $weight,
      'name' => $name,
    ];
    if ($termId) {
      $values['tid'] = $termId;
      $term = Term::load($termId);
      if (NULL !== $term) {
        throw new \RuntimeException(sprintf('Term with ID %d is already defined (%s)', $termId, $term->label()));
      }
    }
    $term = Term::create($values);

    $referenceName = $name;
    if (NULL !== $parent) {
      $term->set('parent', $parent->id());
      $referenceName = $parent->getName() . ':' . $name;
    }

    $this->setReference(static::$vocabularyId . ':' . $referenceName, $term);
    $term->save();

    return $term;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroups() {
    return ['itkdev_example_forms'];
  }

}
