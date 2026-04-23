# itkdev_ex_nemlogin

NemLog-in example forms.

Installing this module will set config that enables mocking citizen login:

1. [/admin/os2forms_nemlogin_openid_connect/settings](https://selvbetjening.local.itkdev.dk/admin/os2forms_nemlogin_openid_connect/settings)
2. [/admin/config/system/os2web-nemlogin](https://selvbetjening.local.itkdev.dk/admin/config/system/os2web-nemlogin)
3. Simulating adding
   `$settings['os2forms_nemlogin_openid_connect']['allow_http'] = TRUE;` to
   `settings.local.php`

If you need to reset the config, you can run

```shell
drush php:eval '\Drupal::service(\Drupal\itkdev_ex_nemlogin\SettingsHelper::class)->setSettings()'
```

(rather than reinstalling the module).

See [`src/SettingsHelper.php`](src/SettingsHelper.php) for details.
