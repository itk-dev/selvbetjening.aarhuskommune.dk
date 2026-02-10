# selvbetjening.aarhuskommune.dk

## Fordelingskomponenten

``` shell
docker compose exec phpfpm composer require "itk-dev/serviceplatformen":"dev-feature/SF2900-Fordelingskomponenten as 1.9.0"
```

-------------------------------------------------------------------------------

## Getting started

These instructions will get you a copy of the project up and running on your
local machine for development and testing purposes.

### Prerequisites

* [Docker](https://www.docker.com/)
* [Docker Compose](https://docs.docker.com/compose/)

### Installation

```sh name=site-install
docker network create frontend || true
docker network create serviceplatformen_organisation_api_app || true
docker compose pull
docker compose up --detach --wait

# Important: Use --no-interaction to make https://getcomposer.org/doc/06-config.md#discard-changes have effect.
docker compose exec phpfpm composer install --no-interaction

# Install the site
docker compose exec phpfpm vendor/bin/drush site:install --existing-config --yes

# Download and install external libraries
docker compose exec phpfpm vendor/bin/drush webform:libraries:download
docker compose exec phpfpm vendor/bin/drush chosen:plugin

# Build theme assets
docker compose run --rm node yarn --cwd web/themes/custom/os2forms_selvbetjening_theme install
docker compose run --rm node yarn --cwd web/themes/custom/os2forms_selvbetjening_theme build

# Open the site
open $(docker compose exec phpfpm vendor/bin/drush --uri=http://$(docker compose port nginx 8080) user:login)
```

The development setup depends on the `serviceplatformen_organisation_api_app`
network which is used to access the API from
[Serviceplatformen organisation API](https://github.com/itk-dev/serviceplatformen_organisation_api).
If you start that project (cf. [Getting started](https://github.com/itk-dev/serviceplatformen_organisation_api/blob/develop/README.md#getting-started)),
you're good to go. If you don't need the API during development,
you can manually create the network by running

```sh
docker network create serviceplatformen_organisation_api_app
```

### Configuration

Some modules included in this project needs additional configuration.
Take a look at the following modules on how to configure them:

* [OS2Forms CPR Lookup](https://github.com/itk-dev/os2forms_cpr_lookup)
* [OS2Forms CVR Lookup](https://github.com/itk-dev/os2forms_cvr_lookup)
* [OS2Forms Digital Post](https://github.com/itk-dev/os2forms_digital_post)
* [OS2Forms NemLogin OpenID Connect](https://github.com/itk-dev/os2forms_nemlogin_openid_connect)
* [OS2Forms GetOrganized](https://github.com/OS2Forms/os2forms_get_organized)
* [OS2Forms Fasit](https://github.com/itk-dev/os2forms_fasit/)

#### CPR and CVR lookups

See `/admin/config/system/os2web-datalookup/cpr-lookup` and
`/admin/config/system/os2web-datalookup/cvr-lookup` for configuration.

### OpenID Connect login

The [OpenID Connect module](https://www.drupal.org/project/openid_connect) is
used to authenticate users and for security reasons the module must be
configured in the `settings.local.php` file:

```php
# settings.local.php
$config['openid_connect.client.generic']['settings']['client_id'] = '…; // Get this from your IdP provider
$config['openid_connect.client.generic']['settings']['client_secret'] = '…'; // Get this from your IdP provider
$config['openid_connect.client.generic']['settings']['authorization_endpoint'] = '…'; // Get this from your OpenID Connect Discovery endpoint
$config['openid_connect.client.generic']['settings']['token_endpoint'] = '…'; // Get this from your OpenID Connect Discovery endpoint

// Set Drupal roles from map IdP roles (in the `groups` claim) on authentication.
$config['openid_connect.settings']['role_mappings']['administrator'] = ['AD-administrator'];
$config['openid_connect.settings']['role_mappings']['forloeb_designer'] = ['GG-Rolle-Digitaleworkflows-forloebsdesigner-prod'];
$config['openid_connect.settings']['role_mappings']['flow_designer'] = ['GG-Rolle-Digitaleworkflows-flowdesigner-prod'];

// Overwrite a translation to show a meaningful text on the log in button.
$settings['locale_custom_strings_en'][''] = [
   'Log in with @client_title' => 'Log in with OpenID Connect (employee)',
];

$settings['locale_custom_strings_da'][''] = [
   'Log in with @client_title' => 'Medarbejderlogin',
];
```

### Selvbetjening Module

The `OS2Forms Selvbetjening` module updates the following:

#### Webform Email Handler

Adds a translatable description to the message body section.

#### Webform category

Adds a translatable description to the webform category selects.

### Maestro

We use the [Maestro module](https://www.drupal.org/project/maestro) to make workflows.

To avoid having to run the
[Orchestrator](https://www.drupal.org/docs/contributed-modules/maestro/installation#s-maestro-engine-also-know-as-the-orchestrator)
manually, a token must be set in
`/admin/config/workflow/maestro`. The Orchestrator can then be run by visiting
`https://[site]/orchestrator/{token}`.
Adding the following cronjob to your crontab will run
the Orchestrator every five minutes.

```cron
*/5 * * * * /usr/bin/curl --location https://[site]/orchestrator/{token} > /dev/null 2>&1; /usr/local/bin/cron-exit-status -c 'Some exit message probably containing [site]' -v $?
```

In `/admin/config/workflow/maestro` you can also configure
whether a refresh of the Maestro Task Console should run the Orchestrator,
which certainly could be an advantage during tests.

### REST API

We use [Webform REST](https://www.drupal.org/project/webform_rest) to expose a
number of API endpoints. See [OS2Forms REST
API](web/modules/custom/os2forms_rest_api/README.md) for details.

### Organisation API

Configure organisation API endpoint on `/admin/os2forms_organisation/settings` to

```sh
http://organisation_api:8080/api/v1/
```

### OS2Forms Email Handler

Overrides default webform email handler adding the option to send
a notification to configured email if attachment size surpasses
a configured value. If this size is surpassed only the notification
email is sent.

By default, no notification is sent.
Enable and configure notifications receivers on the webform settings page.
Configure file size threshold, from email
and from name in `settings.local.php`:

```php
// OS2Forms Email Handler
// File size threshold should be a positive integer followed by a unit.
// Allowed units are KB, MB and GB.
// Examples: 900KB, 3MB, 2GB.
$settings['os2forms_email_handler']['notification_file_size_threshold'] = '10MB';
$settings['os2forms_email_handler']['notification_message_from_email'] = 'noreply@aarhus.dk';
$settings['os2forms_email_handler']['notification_message_from_name'] = 'Selvbetjening';
```

## Production

```sh
composer install --no-dev --optimize-autoloader
```

Install site as described above.

Apply updates by running

```sh
vendor/bin/drush --yes deploy
```

Configure the [`memcache` module](https://www.drupal.org/project/memcache):
<https://git.drupalcode.org/project/memcache/blob/8.x-2.x/README.txt>

## Production Database

The database of production must never be copied to a local development
environment, as its data contains personal data.

If developers need an actual database for local development, the stg-environment
can be made ready for download by ensuring that you delete all submissions and
other information that can have personal character, before downloading.

## Coding standards

```sh
docker compose exec phpfpm composer coding-standards-check
```

```sh
docker compose run node yarn --cwd /app install
docker compose run node yarn --cwd /app coding-standards-check
```

## Testing

### Emails

For development, the [Mail
Debugger](https://www.drupal.org/project/mail_debugger) module is available:

```sh
docker compose exec phpfpm vendor/bin/drush pm:enable mail_debugger
open "http://$(docker compose port nginx 80)/admin/config/development/mail_debugger"
# Open MailHog
open "http://$(docker compose port mailhog 8025)"
```

**Note**: Make sure to not add `mail_debugger` to the `modules` list in
[`config/sync/core.extension.yml`](config/sync/core.extension.yml) – it’s only
for development.

Check your SMTP-settings with

```sh
docker compose exec phpfpm vendor/bin/drush config:get --include-overridden smtp.settings
```

## Admin message

An admin message (shown only on admin pages) can be defined in
`settings.local.php`, e.g.:

```php
$settings['os2forms_selvbetjening']['admin_message'] = 'This is a <strong>test system</strong>';
$settings['os2forms_selvbetjening']['admin_message_style'] = 'padding: 1em; background-color: red; color: yellow;';
```

## Translations

Import translations by running

```sh
docker compose exec --workdir /app/web phpfpm ../vendor/bin/drush locale:import --type=customized --override=none da ../translations/translations.da.po
```

Export translations by running

```sh
docker compose exec --workdir /app/web phpfpm ../vendor/bin/drush locale:export da --types=customized > ./translations/translations.da.po
```

Open `translations/translations.da.po` with the latest version of
[Poedit](https://poedit.net/) to clean up and then save the file.

See
<https://medium.com/limoengroen/how-to-deploy-drupal-interface-translations-5653294c4af6>
for further details.
