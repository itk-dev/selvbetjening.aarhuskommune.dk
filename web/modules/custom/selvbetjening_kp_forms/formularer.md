# Formularer

## [SP 241]

<https://dev-selvbetjening.aarhuskommune.dk/da/admin/structure/webform/manage/ansoegning_om_helbredstillaeg_sp/source?config_entity=ansoegning_om_helbredstillaeg_sp>

## [SP 242]

<https://dev-selvbetjening.aarhuskommune.dk/da/admin/structure/webform/manage/sp242_xsd/source?config_entity=sp242_xsd>

``` yaml
      sygeforsikring_gruppe:
        '#type': select
        '#title': Gruppe
        '#options':
          Gruppe_1: 'Gruppe 1'
          Gruppe_2: 'Gruppe 2'
          Gruppe_5: 'Gruppe 5'
          Gruppe_E: 'Gruppe E'
          Gruppe_N: 'Gruppe N'
          Gruppe_S: 'Gruppe S'
          Gruppe_BASIS: 'Gruppe Basis'
```

bør være

``` yaml
      sygeforsikring_gruppe:
        '#type': select
        '#title': Gruppe
        '#options':
          GRUPPE_1: 'Gruppe 1'
          GRUPPE_2: 'Gruppe 2'
          GRUPPE_5: 'Gruppe 5'
          GRUPPE_E: 'Gruppe E'
          GRUPPE_N: 'Gruppe N'
          GRUPPE_S: 'Gruppe S'
          GRUPPE_BASIS: Basis
```

(ligesom på [SP 241])

Det er forvirrende at nogle "options" slutter med et mellemrum:

``` yaml
      udvidethelbredstillaeg:
        '#type': radios
        '#title': 'Jeg søger om tilskud til nødvendig udgift til'
        '#options':
          'Tandprotese ': 'Tandprotese '
          'Briller ': 'Briller '
          Fodbehandling: Fodbehandling
```

Vi kan sagtens håndtere dette i xml-skabelonen, men det er noget rod at bruge "Tandprotese " når man faktisk mener "Tandprotese".

## [SP 246]

<https://dev-selvbetjening.aarhuskommune.dk/da/admin/structure/webform/manage/erklaering_fra_optiker_sp246_000/source?config_entity=erklaering_fra_optiker_sp246_000>

* `aarsag_specielleforhold` må højst indeholde 200 tegn
* Vi mangler noget der kan fyldes i

  ``` xml
    <UnderskriftsoplysningerBehandler>
      <UnderskriftBehandler>@todo</UnderskriftBehandler>
  ```

## [SP 501]

<https://dev-selvbetjening.aarhuskommune.dk/da/admin/structure/webform/manage/ansoegning_om_personligt_tillaeg/source?config_entity=ansoegning_om_personligt_tillaeg>

* Der kan kun være ét formål i xml'en; formularen tillader flere.
* Håndtering af filer!

  ``` yaml
        fuldmagt_fuldmagtdokumentnavn:
          '#type': managed_file
          '#title': 'Underskrevet fuldmagt for ansøger'
          '#description_display': invisible
          '#required': true
          '#states':
            visible:
              ':input[name="erklaering"]':
                checked: true
          '#format': name
   ```

## Anmodning

<https://dev-selvbetjening.aarhuskommune.dk/da/admin/structure/webform/manage/helbredstillaeg_refundering_af_u/source?config_entity=helbredstillaeg_refundering_af_u>

Nogle valgmuligheder for "AlmindeligtHelbredstillaeg" er ikke gyldige:

``` yaml
      sagstype_almindeligthelbredstillaeg:
        '#type': radios
        '#title': 'Angiv type af behandling, hvortil der ydes helbredstillæg'
        '#options':
          Medicin: Medicin
          Tandbehandling: Tandbehandling
          'Fodbehandling (v. sukkersyge, nedgroede tånegle, arvæv efter strålebehandling og svær leddegigt) Kræver lægehenvisning': 'Fodbehandling (v. sukkersyge, nedgroede tånegle, arvæv efter strålebehandling og svær leddegigt) Kræver lægehenvisning'
          Fysioterapi: Fysioterapi
          Kiropraktik: Kiropraktik
          Psykologhjælp: Psykologhjælp
          'Høreapparatbehandling (v. privat leverandør)': 'Høreapparatbehandling (v. privat leverandør)'
```

jf. [Anmodning.xsd](https://github.com/itk-dev/os2forms_fordelingskomponent/blob/os2forms_fordelingskomponent/resources/SP/SF2900_XSD/Anmodning.xsd).

---

## Lokal udvikling

<table>
<thead>
<tr>
  <td></td>

  <th colspan="2">Local</th>

  <th colspan="2">Dev</th>
</tr>
<tr>
  <td></td>

  <th>Elements</th>
  <th>handlers</th>

  <th>Elements</th>
  <th>handlers</th>
</tr>
</thead>

<tbody>
  <tr>
    <th>SP 241</th>
    <td>
      <a href="https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/sp242_xsd/source?config_entity=sp242_xsd">source</a>
    </td>
    <td>
      <a href="https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/sp242_xsd/handlers?config_entity=sp242_xsd">list</a>
      <a href="https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/sp242_xsd/handlers/fordelingskomponent_sf2900/edit">edit sf2900</a>
    </td>
  </tr>
</tbody>
</table>

|        | Local |   |         | dev |
|--------|-------|---|---------|-----|
|        |       |   | Handler |     |
| SP 241 |       |   |
| SP 242 |
|        |

* https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/sp242_xsd/source?config_entity=sp242_xsd
* https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/sp242_xsd/handlers?config_entity=sp242_xsd
* [handler](https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/sp242_xsd/handlers/fordelingskomponent_sf2900/edit)

### SP 246

* https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/erklaering_fra_optiker_sp246_000/source?config_entity=erklaering_fra_optiker_sp246_000
* https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/erklaering_fra_optiker_sp246_000/handlers?config_entity=erklaering_fra_optiker_sp246_000
* [handler](https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/erklaering_fra_optiker_sp246_000/handlers/fordelingskomponent_sf2900/edit)

### SP 501

* https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/ansoegning_om_personligt_tillaeg/source?config_entity=ansoegning_om_personligt_tillaeg
* https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/ansoegning_om_personligt_tillaeg/handlers?config_entity=ansoegning_om_personligt_tillaeg
* [handler](https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/ansoegning_om_personligt_tillaeg/handlers/fordelingskomponent_sf2900/edit)

### Anmodning



### SP 241

* https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/ansoegning_om_helbredstillaeg_sp/source?config_entity=ansoegning_om_helbredstillaeg_sp
* https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/ansoegning_om_helbredstillaeg_sp/handlers?config_entity=ansoegning_om_helbredstillaeg_sp
* [handler](https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/ansoegning_om_helbredstillaeg_sp/handlers/fordelingskomponent_sf2900/edit)

### SP 242

* https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/sp242_xsd/source?config_entity=sp242_xsd
* https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/sp242_xsd/handlers?config_entity=sp242_xsd
* [handler](https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/sp242_xsd/handlers/fordelingskomponent_sf2900/edit)

### SP 246

* https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/erklaering_fra_optiker_sp246_000/source?config_entity=erklaering_fra_optiker_sp246_000
* https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/erklaering_fra_optiker_sp246_000/handlers?config_entity=erklaering_fra_optiker_sp246_000
* [handler](https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/erklaering_fra_optiker_sp246_000/handlers/fordelingskomponent_sf2900/edit)

### SP 501

* https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/ansoegning_om_personligt_tillaeg/source?config_entity=ansoegning_om_personligt_tillaeg
* https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/ansoegning_om_personligt_tillaeg/handlers?config_entity=ansoegning_om_personligt_tillaeg
* [handler](https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/ansoegning_om_personligt_tillaeg/handlers/fordelingskomponent_sf2900/edit)

### Anmodning

* https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/helbredstillaeg_refundering_af_u/source?config_entity=helbredstillaeg_refundering_af_u
* https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/helbredstillaeg_refundering_af_u/handlers?config_entity=helbredstillaeg_refundering_af_u
* [handler](https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/helbredstillaeg_refundering_af_u/handlers/fordelingskomponent_sf2900/edit)

---

[SP 241]: https://dev-selvbetjening.aarhuskommune.dk/da/admin/structure/webform/manage/ansoegning_om_helbredstillaeg_sp/source?config_entity=ansoegning_om_helbredstillaeg_sp
[SP 242]: https://dev-selvbetjening.aarhuskommune.dk/da/admin/structure/webform/manage/sp242_xsd/source?config_entity=sp242_xsd
[SP 246]: https://dev-selvbetjening.aarhuskommune.dk/da/admin/structure/webform/manage/erklaering_fra_optiker_sp246_000/source?config_entity=erklaering_fra_optiker_sp246_000
[SP 501]: https://dev-selvbetjening.aarhuskommune.dk/da/admin/structure/webform/manage/ansoegning_om_personligt_tillaeg/source?config_entity=ansoegning_om_personligt_tillaeg
[Anmodning]: https://dev-selvbetjening.aarhuskommune.dk/da/admin/structure/webform/manage/helbredstillaeg_refundering_af_u/source?config_entity=helbredstillaeg_refundering_af_u
