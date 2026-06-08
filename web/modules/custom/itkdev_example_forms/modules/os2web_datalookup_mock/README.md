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

The path to the person data file can be overridden by setting the `PERSON_LOOKUP_PATH` environment variable (cf.
[docker-compose.oidc.yml](../../../../../../docker-compose.oidc.yml)).

Reload mock data by running

``` shell
docker compose up --force-recreate idp-citizen
```

Start ["Self login"](http://idp-citizen.selvbetjening.local.itkdev.dk/) on
<http://idp-citizen.selvbetjening.local.itkdev.dk/oidc/login> and check that mock users are defined as expected.

### Editing mock data

Run

``` shell
docker compose run --quiet --rm phpfpm composer install
docker compose run --quiet --rm phpfpm bin/generate-mock-persons
```

to get some inspiration for more or less realistic mock data.
