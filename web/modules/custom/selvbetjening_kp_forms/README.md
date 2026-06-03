# KP forms

This is just a place to keep a backup of the KP forms.

> [!NOTE]
> This module is only intended for development and therefore not supposed to be installed on test or production servers.
> Therefore the scripts below are not implemented as Drush commands or some such fancy stuff.

## Scripts

Import and export webforms and submissions:

``` shell
drush php:script web/modules/custom/selvbetjening_kp_forms/scripts/webforms-export.php
drush php:script web/modules/custom/selvbetjening_kp_forms/scripts/webforms-import.php

drush php:script web/modules/custom/selvbetjening_kp_forms/scripts/submissions-export.php
drush php:script web/modules/custom/selvbetjening_kp_forms/scripts/submissions-import.php
```

Fetch exports from remote server:

``` shell
web/modules/custom/selvbetjening_kp_forms/scripts/fetch
```

## Tips and tricks

Validate XML payloads:

``` shell
drush os2forms-fordelingskomponent:validate-xml ansoegning_om_helbredstillaeg_sp fordelingskomponent_sf2900
drush os2forms-fordelingskomponent:validate-xml sp242_xsd fordelingskomponent_sf2900
drush os2forms-fordelingskomponent:validate-xml erklaering_fra_optiker_sp246_000 fordelingskomponent_sf2900
drush os2forms-fordelingskomponent:validate-xml ansoegning_om_personligt_tillaeg fordelingskomponent_sf2900
drush os2forms-fordelingskomponent:validate-xml helbredstillaeg_refundering_af_u fordelingskomponent_sf2900
```

Re-add a submission to the fordelingskomponent queue:

``` shell
drush php:eval '\Drupal::entityTypeManager()->getStorage("webform_submission")->load(«submission-id»)->save()'
```
