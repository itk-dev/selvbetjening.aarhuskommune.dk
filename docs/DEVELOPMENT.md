# Testing OpenID Connect

We use [OpenId Connect Server Mock](https://github.com/Soluto/oidc-server-mock).

**Note**: The following assumes that [the itkdev-docker-compose helper
script](https://github.com/itk-dev/devops_itkdev-docker#helper-scripts) is used
for development.

Start docker compose show with the `test`
[profile](https://docs.docker.com/compose/profiles/):

```sh
docker compose --profile test up --detach
```

Remember to add `--profile test` when stopping (or downing) to stop (or down)
all containers:

```sh
docker compose --profile test stop
```

## "Medarbejderlogin"

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
  // Set this to the URL of your "You're now signed out" page.
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

When accessing the page you should be redirected to the IdP sign in form. Sign
in with username `1705880000` and password `1705880000` (for CPR user) or with
username `43486829` and password `43486829` (for CVR user) (cf.
`USERS_CONFIGURATION_INLINE` in
[`docker-compose.override.yml`](../docker-compose.override.yml)).

## Test users

* <https://idp-admin.selvbetjening.local.itkdev.dk/api/v1/user/administrator>
* <https://idp-citizen.selvbetjening.local.itkdev.dk/api/v1/user/1705880000>
* <https://idp-citizen.selvbetjening.local.itkdev.dk/api/v1/user/43486829>

## Adding a claim

You can add a claim by modifying `USERS_CONFIGURATION_INLINE` in the relevant
mock idp container in [`docker-compose.override.yml`](../docker-compose.override.yml).

```yaml
USERS_CONFIGURATION_INLINE: |
  - SubjectId: administrator
    Username: administrator
    Password: administrator
    Claims:
    - Type: name
      Value: Admin Jensen
      ValueType: string
    - Type: email
      Value: administrator@example.com
      ValueType: string
    - Type: groups
      Value: '["AD-administrator"]'
      ValueType: json
    # Beneath is added
    - Type: some_new_claim
      Value: integer
      ValueType: 1234
```

and updating `IDENTITY_RESOURCES_INLINE`

```yaml
IDENTITY_RESOURCES_INLINE: |
  # https://auth0.com/docs/get-started/apis/scopes/openid-connect-scopes#standard-claims
  - Name: openid
    ClaimTypes:
      - sub
  - Name: profile
    ClaimTypes:
      - name
      - groups
      - some_new_claim # Added
  - Name: email
    ClaimTypes:
      - email
```

Changes will take effect after reloading the OIDC configuration as explained
beneath.

## Reloading the OIDC configuration

Run

```sh
docker compose stop
docker compose up -d
```

to reload the OIDC configuration.
