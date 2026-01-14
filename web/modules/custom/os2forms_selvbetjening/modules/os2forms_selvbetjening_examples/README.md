# selvbetjening.aarhuskommune.dk examples

```shell
docker compose exec phpfpm vendor/bin/drush pm:enable os2forms_selvbetjening_examples
```

```shell
docker compose exec phpfpm vendor/bin/drush pm:uninstall os2forms_selvbetjening_examples
docker compose exec phpfpm vendor/bin/drush pm:enable os2forms_selvbetjening_examples
```

## Forms

```shell
open "http://$(docker compose port nginx 8080)/admin/structure/webform?search=&category=Example"
```

/form/example-flow-step-1

### Clean up

Remove webform submissions:

```shell
docker compose exec phpfpm vendor/bin/drush webform:purge
```

Maestro tasks:

```shell
docker compose exec phpfpm vendor/bin/drush os2forms-selvbetjening-examples:maestro:task
```

## Flows

```shell
open "http://$(docker compose port nginx 8080)/maestro/templates/list"
```
