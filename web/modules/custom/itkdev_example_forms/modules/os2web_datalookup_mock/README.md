# OS2Web Datalookup Mock

## Installation

``` shell
drush pm:install os2web_datalookup_mock
```

Run

``` shell
drush os2forms-selvbetjening:look-up:cpr 1111110000
```

to check that the mock responds to lookup requests.

## Mock data

The default person lookup mock data sits in
[`src/Plugin/os2web/DataLookup/PersonLookup.yaml`](src/Plugin/os2web/DataLookup/PersonLookup.yaml).

The path to the person data file can be overridden in `settings.local.php`:

``` php
// settings.local.php
$settings['os2web_datalookup_mock']['paths']['PersonLookup'] = __DIR__.'/files/PersonLookup.yaml';
```

### Editing mock data

Run

``` shell
docker compose run --quiet --rm phpfpm composer install
docker compose run --quiet --rm phpfpm bin/generate-mock-persons
```

to get some inspiration for more or less realistic mock data.
