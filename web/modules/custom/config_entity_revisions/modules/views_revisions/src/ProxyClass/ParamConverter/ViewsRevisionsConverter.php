<?php

namespace Drupal\views_revisions\ProxyClass\ParamConverter;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides a proxy class for ViewsRevisionsConverter.
 *
 * @see \Drupal\Component\ProxyBuilder
 */
class ViewsRevisionsConverter implements ParamConverterInterface {

  use DependencySerializationTrait;

  /**
   * The id of the original proxied service.
   *
   * @var string
   */
  protected $drupalProxyOriginalServiceId;

  /**
   * The real proxied service, after it was lazy loaded.
   *
   * @var \Drupal\views_revisions\ParamConverter\ViewsRevisionsConverter
   */
  protected $service;

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Constructs a ProxyClass Drupal proxy object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param string $drupal_proxy_original_service_id
   *   The service ID of the original service.
   */
  public function __construct(ContainerInterface $container, $drupal_proxy_original_service_id) {
    $this->container = $container;
    $this->drupalProxyOriginalServiceId = $drupal_proxy_original_service_id;
  }

  /**
   * Lazy loads the real service from the container.
   *
   * @return object
   *   Returns the constructed real service.
   */
  protected function lazyLoadItself() {
    if (!isset($this->service)) {
      $this->service = $this->container->get($this->drupalProxyOriginalServiceId);
    }

    return $this->service;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    return $this->lazyLoadItself()
      ->convert($value, $definition, $name, $defaults);
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return $this->lazyLoadItself()->applies($definition, $name, $route);
  }

  /**
   * {@inheritdoc}
   */
  public function moduleName() {
    return $this->lazyLoadItself()->moduleName();
  }

  /**
   * {@inheritdoc}
   */
  public function configEntityName() {
    return $this->lazyLoadItself()->configEntityName();
  }

  /**
   * {@inheritdoc}
   */
  public function revisionsEntityName() {
    return $this->lazyLoadItself()->revisionsEntityName();
  }

  /**
   * {@inheritdoc}
   */
  public function settingName() {
    return $this->lazyLoadItself()->settingName();
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    return $this->lazyLoadItself()->title();
  }

  /**
   * {@inheritdoc}
   */
  public function hasOwnContent() {
    return $this->lazyLoadItself()->hasOwnContent();
  }

  /**
   * {@inheritdoc}
   */
  public function contentEntityType() {
    return $this->lazyLoadItself()->contentEntityType();
  }

  /**
   * {@inheritdoc}
   */
  public function contentParameterName() {
    return $this->lazyLoadItself()->contentParameterName();
  }

  /**
   * {@inheritdoc}
   */
  public function contentParentReferenceField() {
    return $this->lazyLoadItself()->contentParentReferenceField();
  }

  /**
   * {@inheritdoc}
   */
  public function adminPermission() {
    return $this->lazyLoadItself()->adminPermission();
  }

  /**
   * {@inheritdoc}
   */
  public function hasCanonicalUrl() {
    return $this->lazyLoadItself()->hasCanonicalUrl();
  }

}
