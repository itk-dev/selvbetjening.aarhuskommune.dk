# ITK Dev OpenID Connect

## Configuration

Edit `settings.local.php` to define your OpenID Connect secret and endpoints:

```php
$config['openid_connect.client.azureb2c']['settings']['authorization_endpoint'] = '…';
$config['openid_connect.client.azureb2c']['settings']['token_endpoint'] = '…';
$config['openid_connect.client.azureb2c']['settings']['client_id'] = '…';
$config['openid_connect.client.azureb2c']['settings']['client_secret'] = '…';
```

```php
// Map IdP groups to Drupal roles.
$config['openid_connect.settings']['role_mappings']['flow_designer'][] = 'flowdesigner';
$config['openid_connect.settings']['role_mappings']['forloeb_designer'][] = 'forloebsdesigner';
// flow- and forløbsdesigner should also be medarbejder
$config['openid_connect.settings']['role_mappings']['medarbejder'][] = 'flowdesigner';
$config['openid_connect.settings']['role_mappings']['medarbejder'][] = 'forloebsdesigner';
```

## Patches applied

Excerpt from `composer.json`:

```json
{
    …
    "extra": {
        "patches": {
            …,
            "drupal/openid_connect": {
                "Revoking group access does not reflect on applied roles (https://www.drupal.org/project/openid_connect/issues/3224128)": "https://git.drupalcode.org/project/openid_connect/-/merge_requests/31.diff",
                "Use role as groups": "./web/modules/custom/itkdev_openid_connect/patches/role-to-groups.patch"
            }
        }
    }
}
```
