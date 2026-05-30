# Testing OpenID Connect

We use [OpenID Provider Mock](https://github.com/geigerzaehler/oidc-provider-mock) to the OIDC login during development.

For "[Citizen login](#citizen-login)", we generate the mock OIDC users based on [mock CPR lookup data](../web/modules/custom/itkdev_example_forms/modules/os2web_datalookup_mock/resources/PersonLookup.yaml),
see [docker-compose.oidc.yml](../docker-compose.oidc.yml) for details.

## "Medarbejderlogin"

> [!WARNING]
> The content in this section is outdated.

Configure [the OpenID Connect
module](https://www.drupal.org/project/openid_connect) to use
<https://idp-admin.selvbetjening.local.itkdev.dk> as identity provider (cf. [the
discovery
document](https://idp-admin.selvbetjening.local.itkdev.dk/.well-known/openid-configuration)):

```php
# web/sites/default/settings.local.php
…
// http://idp-admin.selvbetjening.local.itkdev.dk/.well-known/openid-configuration
$config['openid_connect.client.generic']['settings']['client_id'] = 'client-id;
$config['openid_connect.client.generic']['settings']['client_secret'] = 'client-secret';
$config['openid_connect.client.generic']['settings']['authorization_endpoint'] = 'https://idp-admin.selvbetjening.local.itkdev.dk/connect/authorize';
// Note that we use `http` here (and not `https`) as the token endpoint is accessed inside the docker compose setup.
$config['openid_connect.client.generic']['settings']['token_endpoint'] = 'http://idp-admin.selvbetjening.local.itkdev.dk/connect/token';
```

Go to <https://selvbetjening.local.itkdev.dk/user/login>, click
“Medarbejderlogin” and sign in as `administrator` with password `administrator`
(cf. `USERS_CONFIGURATION_INLINE` in [`docker-compose.override.yml`](../docker-compose.override.yml)).

## Citizen login

> [!WARNING]
> The content in this section is outdated.

```php
# web/sites/default/settings.local.php
…
$config['os2web_nemlogin.settings']['OpenIDConnect'] = serialize([
  'plugin_id' => 'OpenIDConnect',
  // Note that we use `http` here (and not `http`) as the token endpoint is accessed inside the docker compose setup.
  // Be aware, that with current implementation of itk-dev/openid-connect underscores '_' are not allowed in a discovery urls subdomain.
  'nemlogin_openid_connect_discovery_url' => 'http://idp-citizen.selvbetjening.local.itkdev.dk/.well-known/openid-configuration',
  'nemlogin_openid_connect_client_id' => 'client-id',
  'nemlogin_openid_connect_client_secret' => 'client-secret',
  'nemlogin_openid_connect_fetch_once' => 0,
    // Set this the url of your "You're not signed out" page.
  'nemlogin_openid_connect_post_logout_redirect_uri' => '/node/126',
  'nemlogin_openid_connect_user_claims' => 'cpr: CPR-nummer
email: E-mailadresse',
]);
$config['os2web_nemlogin.settings']['active_plugin_id'] = 'OpenIDConnect';

// Allow HTTP scheme in OIDC urls.
$settings['os2forms_nemlogin_openid_connect']['allow_http'] = TRUE;
```

Create a public form with "Webform type" set to "Personal" and a `webform` page
using the form.

## Reloading the OIDC configuration

Run

```sh
docker compose stop
docker compose up -d
```

to reload the OIDC configuration.

## Generating webform submissions

``` shell
docker compose exec phpfpm vendor/bin/drush pm:install devel_generate
docker compose exec phpfpm vendor/bin/drush --help
```
