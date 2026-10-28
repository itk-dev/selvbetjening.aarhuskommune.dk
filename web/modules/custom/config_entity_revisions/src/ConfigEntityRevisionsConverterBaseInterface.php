<?php

namespace Drupal\config_entity_revisions;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

/**
 * Interface for ConfigEntityRevisions parameter converter.
 */
interface ConfigEntityRevisionsConverterBaseInterface extends ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults);

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route);

  /**
   * Determines the entity type ID given a route definition and route defaults.
   *
   * @param mixed $definition
   *   The parameter definition provided in the route options.
   * @param string $name
   *   The name of the parameter.
   * @param array $defaults
   *   The route defaults array.
   *
   * @return string
   *   The entity type ID.
   *
   * @throws \Drupal\Core\ParamConverter\ParamNotConvertedException
   *   Thrown when the dynamic entity type is not found in the route defaults.
   */
  public function getEntityTypeFromDefaults($definition, $name, array $defaults);

}
