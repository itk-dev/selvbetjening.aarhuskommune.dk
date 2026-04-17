<?php

declare(strict_types=1);

namespace Drupal\itkdev_ex_nemlogin;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Site\Settings;
use Drupal\os2forms_nemlogin_openid_connect\Helper\Settings as Os2formsNemloginOpenidConnectSettings;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Hacks and tricks to set config and settings.
 */
final class SettingsHelper implements EventSubscriberInterface {

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly Os2formsNemloginOpenidConnectSettings $os2formsNemloginOpenidConnectSettings,
    private readonly Settings $settings,
    #[Autowire(service: 'logger.channel.itkdev_ex_nemlogin')]
    private readonly LoggerChannelInterface $logger,
  ) {
  }

  /**
   * Set config.
   */
  public function setConfig() : void {
    $this->logger->info('Setting %name settings', ['%name' => 'os2forms_nemlogin_openid_connect']);
    $this->os2formsNemloginOpenidConnectSettings
      ->setSettings([
        'providers' => Yaml::encode([
          'openid_connect_nemlogin' => 'OpenIDConnect Nemlogin (set by itkdev_ex_nemlogin)',
        ]),
      ]);

    $config = $this->configFactory->getEditable('os2web_nemlogin.settings');
    $this->logger->info('Setting %name config', ['%name' => $config->getName()]);
    $config
      ->setData([
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
      ])
      ->save();
  }

  /**
   * Set settings.
   */
  public function setSettings(): void {
    $settings = $this->settings;
    $prop = new \ReflectionProperty($settings, 'storage');
    $value = $prop->getValue($settings);
    $value['os2forms_nemlogin_openid_connect']['allow_http'] = TRUE;
    $prop->setValue($settings, $value);
  }

  /**
   * Kernel request event handler.
   */
  public function onKernelRequest(RequestEvent $event): void {
    if (preg_match(
      '/^os2forms_nemlogin_openid_connect\./',
      (string) $event->getRequest()->attributes->get('_route'),
    )) {
      $this->setSettings();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onKernelRequest'],
    ];
  }

}
