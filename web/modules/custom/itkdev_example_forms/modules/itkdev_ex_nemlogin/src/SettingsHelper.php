<?php

declare(strict_types=1);

namespace Drupal\itkdev_ex_nemlogin;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
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
    private readonly Os2formsNemloginOpenidConnectSettings $os2formsNemloginOpenidConnectSettings,
    private readonly Settings $settings,
    #[Autowire(service: 'logger.channel.itkdev_ex_nemlogin')]
    private readonly LoggerChannelInterface $logger,
  ) {
  }

  /**
   * Set settings.
   */
  public function setSettings() : void {
    $this->logger->info('Setting %name settings (%url)', [
      '%name' => 'os2forms_nemlogin_openid_connect',
      '%url' => Url::fromRoute('os2forms_nemlogin_openid_connect.admin.settings')->setAbsolute()->toString(TRUE)->getGeneratedUrl(),
    ]);
    $this->os2formsNemloginOpenidConnectSettings
      ->setSettings([
        'providers' => Yaml::encode([
          'openid_connect_nemlogin' => 'OpenIDConnect Nemlogin (set by itkdev_ex_nemlogin)',
        ]),
      ]);

    // We need to rebuild cache after setting settings.
    drupal_flush_all_caches();
  }

  /**
   * Override settings.
   */
  public function overrideSettings(): void {
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
      $this->overrideSettings();
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
