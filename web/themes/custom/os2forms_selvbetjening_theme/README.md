# selvbetjening.aarhuskommune.dk

## "Det Fælles Designsystem"

Styling fetched from "Det Fælles Designsystem" are located in assets/scss/dfds/ folder. The assets/scss/dfds/dfds.scss file imports the desired components

Current version of the design system 11.2

Used components:
 - [Step indicator](https://designsystem.dk/komponenter/trinindikator/)
 - [Modal](https://designsystem.dk/komponenter/modal)

## PDF templates

Two templates, `os2forms-attachment--webform-submission.html.twig` and
`os2forms-selvbetjening-maestro-notification-pdf-html.html.twig`, are used to render os2forms attachment and maestro notification (pdf) html,
respectively (the templates are used to render PDF files sent as Digital post).

The maestro notification pdf template is configured in `settings.php` as

```sh
themes/custom/os2forms_selvbetjening_theme/templates/pdf/os2forms-selvbetjening-maestro-notification-pdf-html.html.twig
```

which you can override in `settings.local.php` as

```sh
$config['os2forms_forloeb.settings']['templates']['notification_pdf'] = 'path/to/template';
```

The os2forms attachment template should automatically be used.

To allow usage of a common stylesheet in the two templates you
can override the default value (see `settings.php`) in `settings.local.php`:

```php
$settings['base_url'] = 'http://nginx:8080';
```

and disable default css in the entity print module. This is done by
unchecking `Enable Default CSS` on `admin/config/content/entityprint`.


## Webform e-mail template

We override the default webform email template to add table styling.

See `templates/webform/webform-email-html.html.twig`.

## Theme usage

```sh
docker-compose run --rm node yarn --cwd /app/web/themes/custom/os2forms_selvbetjening_theme install
docker-compose run --rm node yarn --cwd /app/web/themes/custom/os2forms_selvbetjening_theme build
```

## Coding standards

```sh
docker-compose run --rm node yarn --cwd /app/web/themes/custom/os2forms_selvbetjening_theme check-coding-standards
```

```sh
docker-compose run --rm node yarn --cwd /app/web/themes/custom/os2forms_selvbetjening_theme apply-coding-standards
```
