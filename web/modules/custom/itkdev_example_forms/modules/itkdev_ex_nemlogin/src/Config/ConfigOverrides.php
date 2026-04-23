<?php

namespace Drupal\itkdev_ex_nemlogin\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Serialization\Yaml;

/**
 * Config overrides.
 */
final class ConfigOverrides implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    if (in_array('os2web_nemlogin.settings', $names)) {
      $overrides['os2web_nemlogin.settings'] = [
        'openid_connect_nemlogin' => serialize([
          'plugin_id' => 'OpenIDConnect',
          'secret_provider' => 'form',
          // Note that we use `http` here (and not `https`) as the token
          // endpoint is accessed inside the docker compose setup.
          'nemlogin_openid_connect_discovery_url' => 'http://idp-citizen.selvbetjening.local.itkdev.dk/.well-known/openid-configuration',
          'nemlogin_openid_connect_client_id' => 'client-id',
          'nemlogin_openid_connect_client_secret' => 'client-secret',
          'nemlogin_openid_connect_fetch_once' => 0,
          // Set this the url of your "You're now signed out" page.
          'nemlogin_openid_connect_post_logout_redirect_uri' => '/',
          'nemlogin_openid_connect_user_claims' => Yaml::encode([
            'cpr' => 'CPR-nummer',
            'email' => 'E-mailadresse',
          ]),
        ]),
        'active_plugin_id' => 'openid_connect_nemlogin',
      ];
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return self::class;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
