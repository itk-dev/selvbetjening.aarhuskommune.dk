<!-- markdownlint-disable MD024 -->

# Changelog for selvbetjening.aarhuskommune.dk

Nedenfor ses dato for release og beskrivelse af opgaver som er implementeret.

## [Under udvikling]

* Tilføjede handler, element og taksonomi oversigter.
* Tilføjede dokumentation af [elementer](docs/ELEMENTS.md) og
  [handlers](docs/HANDLERS.md).

## [4.6.7] 2025-12-18

* Opdaterede ClamAV version.

## [4.6.6] 2025-11-24

* Pakke-opdateringer heriblandt Drupal `10.5`

## [4.6.5] 2025-11-17

* Booking opdateringer
  * Opdaterede booking setup eksempel.
  * Tilføjede oplysningstekst til booking emne inputfeltet.
  * Tilpasning af kalender ressourcenavn visning.
  * Tiljusterede liste-visningen af ressourcer.
  * Opdaterede filtre visninger.
  * Tilføjede Aarhus Kommune logo fallback til ressourcer.
  * Opdaterede resource test data.
  * Tilføjede teleslynge faciliteter og visninger.

## [4.6.4] 2025-11-10

* Tilføjede ny redaktørrolle [PR-451](https://github.com/itk-dev/os2forms_selvbetjening/pull/451)
  * Ændrede permissions på eksisterende roller
  * Tilføjede modul til styring af mere specifikke rettigheder i forms
  * Tilføjede rolle "Redaktør"
  * Ændre adgang til at slette indsendelser så det kræver specifik adgang og ikke
    blot webform redigerings adgang.

## [4.6.3] 2025-11-04

* Formular frontend rettelser
  * Justerede formular knapper,`næste`, `forrige` og `indsend`.
  * Justerede radioknapper og checkbokse.
  * Tilføjede luft under labels.
  * Ændrede tekststørrelser på `h`-elementer og standard tekst.
  * Justerede organisationsdata søgeknappen.
  * Justerede størrelsen af tekstområde-elementet.
  * Justerede visningen af elementbeskrivelser.
* Opdaterede styling i flexbox layout.
  * Sikrede at checkbox labels og beskrivelser laver linjeskift.
  * Tiljusterede placeringen af checkbox input felter.
* Tilføjede javascript til `DAWA adresse`-elementer så de håndterer kommaer [PR-446](https://github.com/itk-dev/os2forms_selvbetjening/pull/446).
* Sikrede at Digtial Post afsender sættes korrekt
* Sikrede at vilkår virker på Maestro-notifikation handleren
* Tiljusterede MitID Children Select elementet så børn kan afvælges.
* Sikrede at e-mail ikke sendes dobbelt ved navne- og adressebeskyttelse.
* Sikrede at automatisk export kan gemme indstillinger.

## [4.6.2] 2025-09-15

* Tilføjede apostrof-regel til kodestandarder [PR-423](https://github.com/itk-dev/os2forms_selvbetjening/pull/423).
* Tilføjede maestro bycontentfunction validation [PR-423](https://github.com/itk-dev/os2forms_selvbetjening/pull/423).
* Tilføjede oversætbar bycontentfunction hjælpetekst [PR-443](https://github.com/itk-dev/os2forms_selvbetjening/pull/443).
* Rettede henvisning til logo i notifikationsskabelon [PR-448](https://github.com/itk-dev/os2forms_selvbetjening/pull/448)

## [4.6.1] 2025-09-08

* GO-handler kører nu kun på afsluttede indsendelser.

## [4.6.0] 2025-09-01

* Opdaterede `os2web/os2web_datalookup` således at resultet af opslag benyttes
  i stedet for det der blev slået op på.
* Opgraderede til MeMo 1.2 ifb. digital post.
* Fjernede ikke-tal fra modtager ifb. med Maestro-digital post-notifikationer.
* Sikrede at fejl ifb. med Maestro-digtial post-notfikationer rapporteres.

## [4.5.0] 2025-07-03

* Tilføjede `site_status_message`-modulet.
* Opdaterede fejlede jobs personaliseret view.
* Fjernede ubrugt custom modul.

## [4.4.0] 2025-06-16

* Tilføjede tilpasset AdvancedQueueProcessor til os2forms selvbetjening [PR-412](https://github.com/itk-dev/os2forms_selvbetjening/pull/412)
* Tilføjede personaliseret view til konfiguration [PR-412](https://github.com/itk-dev/os2forms_selvbetjening/pull/412)

## [4.3.0] 2025-06-03

* Opdaterede `os2forms`.
  * Opdatering af`coc_forms_auto_export` og tillod dermed automatisk eksport
    af indesendelser i form af csv.
* Tilføjede mulighed for ændring af ejerskab på node [PR-421](https://github.com/itk-dev/os2forms_selvbetjening/pull/421).
* OS2Forms kø emails
  * Håndterede formularer der ikke gemmer indsendelser.
  * Opdaterede fejlbeskeder.
* Opdaterede til nyeste version af kø-systemet,
  [advancedqueue](https://www.drupal.org/project/advancedqueue).

## [4.2.1] 2025-05-06

* Sikrede at OS2Forms attachment elementer bliver detekteret korrekt
  i mails gennem kø.

## [4.2.0] 2025-05-06

* Tilpassede os2forms_custom_view_builders for at sikre underelementer
  af container konfigurerede elementer bliver vist.
* Tilføjede mulighed for mails gennem kø.

## [4.1.0] 2025-04-08

* Ændrede PDF tabel visning [PR-404](https://github.com/itk-dev/os2forms_selvbetjening/pull/404)
* Ændrede kort date/time format
* Ændrede PDF headers [PR-386](https://github.com/itk-dev/os2forms_selvbetjening/pull/386).
* Ændrede PDF udseende [PR-377](https://github.com/itk-dev/os2forms_selvbetjening/pull/377).
* Tilføjede os2forms_custom_view_builders module [PR-379](https://github.com/itk-dev/os2forms_selvbetjening/pull/379).

## [4.0.1] 2025-04-01

* [PR-409](https://github.com/itk-dev/os2forms_selvbetjening/pull/409)
  Opdaterede `itk-dev/serviceplatformen` til at bruge nye api-endpoints.

## [4.0.0] 2025-03-25

* Følg `os2forms` core major version.
* Indeholder det samme som `3.2.10`.

## [3.2.10] 2025-03-25

* Deaktiverede login-formularen.
* Opdaterede til `os2forms` 4.0.0.
  * Opdaterede `os2forms_nemlogin_openid_connect` modulet.
  * Opdaterede `os2forms_user_field_lookup` modulet.
  * Opdaterede `os2forms_forloeb_profile` modulet.
  * Opdaterede `os2forms_get_organized` modulet.
  * Opdaterede `os2forms_rest_api` modulet.

## [3.2.9] 2025-03-12

* Opdaterede `os2web_audit` modulet.
* Opdaterede `os2form_failed_jobs` modulet.

## [3.2.8] 2025-03-06

* Opdaterede `os2web_audit` modulet.

## [3.2.7] 2025-03-03

* Tilføjede patch der undgår container titler i e-mails
  når de ikke har underelementer.
* Tillod tabel elementer i `webform` tekstformat.
* Tilføjede styling af tabel elementer i webform udsendte e-mails.
* Tilføjede ekstra tjek i OS2Forms email handler.
* Opdaterede `os2web_audit` modulet.
* Deaktiverede formular ajax muligheden.
* Opdaterede `os2forms_payment` modulet.

## [3.2.6] 2025-02-20

* Opdaterede Drupal core og diverse andre pakker.

## [3.2.5] 2025-02-11

* Opdaterede `os2forms_payment`.
* Opdaterede `os2forms` core modulet.
  * Telefonnummer håndtering i FBS.
* Øgede tilladt hukommelsesforbrug.
* Opdaterede installationsvejledning.

## [3.2.4] 2025-01-07

* Opdaterede `os2forms` core modulet.
* Opdaterede `os2forms_fbs_handler` kø konfiguration.

## [3.2.3] 2025-01-07

* Opdaterede `os2forms` core modulet.
  * Skiftede til FBS modulet i `os2forms` core.
  * Sikrede at Maestro notifikationshandleren gemmer besked format.
* Slog `NemID CPR Fetch data`-elementet fra.
* Opdaterede `os2forms_get_organized`.
  * Normaliserer white spaces i filnavne.

## [3.2.2] 2024-12-19

* Automatisk sletning af vellykkede audit logging jobs.

## [3.2.1] 2024-12-19

* Opdaterede `os2forms_rest_api`
  * Performance opdatering af `Webform Submissions`-endpointet.

## [3.2.0] 2024-12-17

* Tilføjede `os2web_audit`-kø-konfiguration.
* Audit logging af organisationsdata opslag.
* Audit logging af OpenIDConnect autentificering.
* Audit logging af e-mails.
* Løst problem angående skalér scroller til top på klik.
* Tilføjede patch der retter fejl angående æøå i webform søgning.
* Tilføjede update site tjek til GitHub Actions.
* Opdaterede
  [OS2Forms GetOrganized](https://github.com/OS2Forms/os2forms_get_organized) version.
* Opdaterede FBS handler til nyeste endpoints og operations.
* Opdaterede ckeditor konfiguration til
  altid at vise værktøjslinje.
* Opdaterede [OS2Web Datalookup](https://github.com/OS2web/os2web_datalookup/) version.
* Opdaterede `os2forms_payment`.
* Opdaterede til OS2Forms 3.21.0
  * Fasit handler og audit logging
  * Audit logging af digital post
  * Audit logging af nemid felter
  * Audit logging af FBS handler
* Fjerende direkte requirement af `os2forms/os2forms_fasit`.
  * Denne kommer nu gennem OS2Forms core.

## [3.1.1] 2024-11-25

* Opdaterede max input vars i docker-setup.

## [3.1.0] 2024-11-22

* Opdaterede til OS2Forms 3.17.0
  * Audit logging af CPR og CVR opslag
  * Audit logging af login

## [3.0.1] 2024-11-18

* Håndterede ingen vedhæftede filer i forbindelse med
  tjek af for store filer i emails.
* Tilføjede patch til vilkår baseret på computed twig.

## [3.0.0] 2024-11-12

* Tilføjede "Begrænset HTML (Maestro)"-tekstformat.
* Tilføjede mulighed for csv eksport af alle formular konfigurationer.
* Opdaterede docker-compose node setup.
* Opdaterede docker-setup.
* Opdaterede til PHP 8.3
* Opdaterede til Drupal 10.
  * Opdaterede konfiguration.
  * Opdaterede custom moduler.
* Opdaterede til OS2Forms 3.16.2.
* Fjernede ubenyttede temaer.
* Fjernede `keyboard_shortcuts`-modulet.
* Opdaterede [Azure Key Vault](https://github.com/itk-dev/AzureKeyVaultPhp) for
  at rette "Cannot fetch SAML token"-fejl.

## [2.8.3] 2024-09-23

* Tilføjede patch der fjerner adressebeskyttelsestekst på CPR-elementer.

## [2.8.2] 2024-09-17

* Opdaterede [OS2Forms Fasit](https://github.com/itk-dev/os2forms_fasit)
  version.
* Tilføjede mulighed for at sende notifikation hvis
  størrelse på e-mailvedhæftninger overstiger grænse. (<https://os2forms-leantime.itkdev.dk/tickets/showKanban#/tickets/showTicket/132>)
* Opdaterede [OS2Forms Organisation](https://github.com/itk-dev/os2forms_organisation)
  version. (<https://os2forms-leantime.itkdev.dk/tickets/showKanban#/tickets/showTicket/96>)
* Tilføjede signatur-element patches. (<https://os2forms-leantime.itkdev.dk/tickets/showKanban#/tickets/showTicket/133>)
* CKEditor 5 link standard `https` protocol. (<https://os2forms-leantime.itkdev.dk/tickets/showKanban#/tickets/showTicket/168>)

## [2.8.1] 2024-08-26

* Oprydning af konfiguration.
* Tilføjede manglende html editor konfiguration.
* Installerede [CKEditor 5](https://www.drupal.org/docs/core-modules-and-themes/core-modules/ckeditor-5-module).
* Installerede [Editor Advanced link](https://www.drupal.org/project/editor_advanced_link).
* Tilføjede nyt `webform` tekstformat.

## [2.8.0] 2024-08-19

* Tilføjede `os2forms_fasit`-kø-konfiguration.
* Fjernede ekstra OS2Forms indstillingsfaneblad på formularer.
* Opdaterede [OS2Forms](https://github.com/os2forms/os2forms/) version.
* Opdaterede [OS2Web Datalookup](https://github.com/OS2web/os2web_datalookup/) version.
* Tilføjede templates til ændring af `os2forms_attachment` og
  maestro-pdf-notifikationer i `os2forms_selvbetjening_theme`-temaet.
* Gav roller adgang til at se encrypted values.

## [2.7.14] 2024-07-02

* Tilføjede patch af `polyfill`

## [2.7.13] 2024-06-03

* Tilføjede adgangstjek på indsendelser.

## [2.7.12] 2024-05-24

* Opdaterede [GetOrganized API](https://github.com/itk-dev/getorganized-api-client-php/)
  for at håndtere ikke tilladte tegn i filnavne.

## [2.7.11] 2024-05-02

* Opdaterede Maestro flows user autocomplete til at søge på navn.
* Opdaterede styling på maestro flow task menu.
* Opdaterede uploadgrænser i `docker-compose.server.override.yml`.
* Tilføjet virus scanning af fil upload.
* Opdaterede [OS2Forms Fasit](https://github.com/itk-dev/os2forms_fasit/) version.

## [2.7.10] 2024-04-29

* Updated nginx configuration
* Updated tecnickcom/tcpdf package version

## [2.7.9] 2024-04-04

* Tilføjede `brugere_og_id` og `formularer_og_kategori` views.
* Installerede [OS2Forms Fasit](https://github.com/itk-dev/os2forms_fasit/).

## [2.7.8] 2024-03-08

* Opdaterede til [OS2Forms NemLogin OpenID Connect
  2.0.1](https://github.com/itk-dev/os2forms_nemlogin_openid_connect/releases/tag/2.0.1)

## [2.7.7] 2024-02-15

* Opdaterede installationsguide.
* Applied Maestro notification patch
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/270>)
* Opdaterede til [OS2Forms REST API
  2.0.3](https://github.com/OS2Forms/os2forms_rest_api/releases/tag/2.0.3)
  * Håndterede `OS2Forms Attachment`-elementet
  * Opdaterede API adgangskontrol
* Brugte session type til at generere url'er.

## [2.7.6] 2024-02-06

* Opdaterede flow opgave notifikationstilladelser.

## [2.7.5] 2024-01-29

* Tilføjede logout link på flow siden.

## [2.7.4] 2024-01-25

* Opdaterede til [OS2Forms Organisation
  2.0.1](https://github.com/itk-dev/os2forms_organisation/releases/tag/2.0.1).
* Opdaterede 4xx page logout link

## [2.7.3] 2024-01-16

* Opdaterede til [OS2Forms Organisation
  2.0.0](https://github.com/itk-dev/os2forms_organisation/releases/tag/2.0.0).
* Booking resources filter adjustments to better fit map view state
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/264>)

## [2.7.2] 2024-01-15

* Fix bug in booking params
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/267>)

## [2.7.1] 2024-01-09

* Opdaterede til [OS2Forms failed jobs
  1.4.0](https://github.com/itk-dev/os2forms_failed_jobs/releases/tag/1.4.0)
* Opdaterede til [OS2Forms GetOrganized
  1.1.5](https://github.com/OS2Forms/os2forms_get_organized/releases/tag/1.1.5)
* Add filtering to booking widget
(<https://github.com/itk-dev/os2forms_selvbetjening/pull/262>)
* Skjulte duplikerede menu links.
* Tilføjede 4xx page template med logout link
(<https://github.com/itk-dev/os2forms_selvbetjening/pull/258>)
* Opdaterede til [OS2Forms
  3.13.3](https://github.com/OS2Forms/os2forms/releases/tag/3.13.3).
* Tilføjede praktiske CPR- og CVR-opslagskommandoer.
* Skiftede til digital post-modulet i OS2Forms
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/229>)
* Map improvements, rendering of resources, filtering on map view.

## [2.7.0] 2023-12-04

* Changed permissions to webform submission log.
* Opdaterede [OS2Forms failed jobs
  1.3.2](https://github.com/itk-dev/os2forms_failed_jobs/releases/tag/1.3.2)
* Deaktiverede Attachment PDF-elementet.
* Opdaterede til [OS2Forms REST API
  2.0.1](https://github.com/OS2Forms/os2forms_rest_api/releases/tag/2.0.1)
  * Tilføjede REST API endpoint til at hente liste af submissions på form.
    Se [OS2Forms REST API endpoints](https://github.com/OS2Forms/os2forms_rest_api#endpoints).
* Opdaterede til [OS2Forms organisation
  1.3.3](https://github.com/itk-dev/os2forms_organisation/releases/tag/1.3.3)
* Tilføjede beskrivelsestekst til email-handler.

## [2.6.8] 2023-11-16

* itk-dev/itkdev-booking: Fixed issue with location name, edit/delete bookings.

## [2.6.7] 2023-11-08

* Allowed composite elements in Maestro notification recipient.

## [2.6.6] 2023-11-02

* Fixed issue with pending bookings in itkdev-booking.
* Removed itk-dev/itkdev-booking from composer.

## [2.6.5] 2023-10-30

* Handled nested elements in webform inherit in patch

## [2.6.4] 2023-10-26

* Built new assets for itkdev_booking

## [2.6.3] 2023-10-26

* Fixed type error in booking handler
* Converted internal notation of business hours
  for sunday from (7) to (0)

## [2.6.2] 2023-10-13

* Opdaterede `composer.lock`-hash

## [2.6.1] 2023-10-13

* Tilføjede patch til Maestro-tokenhåndtering.

## [2.6.0] 2023-10-12

* Tilføjede custom modulet `os2forms_permission_alterations`.
* Tilføjede `administer leaflet layers` permission til site admin rollen.
* Changed user bookings to use cached bookings.
* Opdaterede dependencies
* Opdaterede til [OS2Forms NemLogin OpenID Connect
  2.0.0](https://github.com/itk-dev/os2forms_nemlogin_openid_connect/releases/tag/2.0.0)
* Added missing config for updated `os2forms_forloab` module.
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/228>)
* Opdaterede til [OS2Forms organisation
  1.3.2](https://github.com/itk-dev/os2forms_organisation/releases/tag/1.3.2)

## [2.5.0] 2023-10-04

* Tilføjede drupal leaflet til config ignore.
* Opdaterede til [OS2Forms GetOrganized
  1.1.4](https://github.com/OS2Forms/os2forms_get_organized/releases/tag/1.1.4)
* Nedgraderede til `drupal/leaflet` `10.0.12`
* Fixed editing/deleting bookings errors in itkdev_booking.
* Disable access to webform error log
* Added retry action to error log
* Changed failed jobs view
* Opdaterede til [OS2Forms failed jobs to
1.3.1](https://github.com/itk-dev/os2forms_failed_jobs/releases/tag/1.3.1)
* Opdaterede til [Beskedfordeler
1.1.1](https://github.com/itk-dev/beskedfordeler-drupal/releases/tag/1.1.1)
* Opdaterede til [OS2Forms organisation
1.3.1](https://github.com/itk-dev/os2forms_organisation/releases/tag/1.3.1)
* Opdaterede til [OS2Forms sync
1.1.3](https://github.com/itk-dev/os2forms_sync/releases/tag/1.1.3)
* Enable os2forms_webform_maps and related contrib modules

## [2.4.9] 2023-09-06

* Tilføjede webform options config ignore.
* Opdaterede til `os2forms/os2forms_digital_post` `3.0.1`.
* Fjernede `itk-dev/os2forms_cpr_lookup` og `itk-dev/os2forms_cvr_lookup`
* Opdaterede til [Beskedfordeler drupal
  1.1.1](https://github.com/itk-dev/beskedfordeler-drupal/releases/tag/1.1.1)
* Tilføjede patch for at undgå honeypot og ajax issues.

## [2.4.8] 2023-08-29

* Moved itkdev_booking module into custom module folder
* Aktiverede Webform `container` elementet.

## [2.4.7] 2023-08-24

* Opdaterede fra `itk-dev/itkdev-booking` `1.0.6`
  til `itk-dev/itkdev-booking` `1.0.7`.

## [2.4.6]

* Opdaterede fra `itk-dev/itkdev-booking` `1.0.5`
  til `itk-dev/itkdev-booking` `1.0.6`.

## [2.4.5]

* Opdaterede fra `itk-dev/itkdev-booking` `1.0.3`
  til `itk-dev/itkdev-booking` `1.0.5`.
* Opdaterede fra `itk-dev/os2forms_digital_post` `2.0.1`
  til `os2forms/os2forms_digital_post` `2.0.2`.

## [2.4.4]

* Opdaterede selvbetjening tema's favicon.
* Opdaterede til [OS2Forms Digital Post
  2.0.1](https://github.com/itk-dev/os2forms_digital_post/releases/tag/2.0.1)

## [2.4.3]

* Opdaterede til [OS2Forms OS2Forms
  3.8.0](https://github.com/OS2Forms/os2forms/releases/tag/3.8.0)
* Tilføjede [OpenId Connect Server
  Mock](https://github.com/Soluto/oidc-server-mock) til test af OIDC-login under
  udvikling.

## [2.4.2]

* Opdaterede til [OS2Forms GetOrganized
  1.1.3](https://github.com/OS2Forms/os2forms_get_organized/releases/tag/1.1.3)

## [2.4.1]

* Fjernede [REST UI](https://www.drupal.org/project/restui)-modulet

## [2.4.0]

* Opdaterede til [OS2Forms med Forløb installation profile
1.12.0](https://github.com/OS2Forms/os2forms_forloeb_profile/releases/tag/1.12.0)
og [OS2Forms 3.7.0](https://github.com/OS2Forms/os2forms/releases/tag/3.7.0)

## [2.3.1]

* Opdaterede til [OS2Forms Digital Post
  2.0.0](https://github.com/itk-dev/os2forms_digital_post/releases/tag/2.0.0)
* Opdaterede til [OS2Forms GetOrganized
  1.1.2](https://github.com/OS2Forms/os2forms_get_organized/releases/tag/1.1.2)

## [2.3.0]

* Opdaterede til [OS2Web Data lookup
  1.6.0](https://github.com/OS2web/os2web_datalookup/releases/tag/1.6.0).
* Fix bug in login display where login link displayed on pages without login
  enabled.
* Installerede `Webform Validation` modulet.
* Minor type fixes to FBS handler
* Tilføjede patch for at tillade filtrering af formularer på kategori
  <https://www.drupal.org/files/issues/2022-10-07/3313766-8.patch>.
* Tilføjede mulighed for at søge på navne på brugerlisten.
* Opdaterede til [OS2Forms sync
  1.1.2](https://github.com/itk-dev/os2forms_sync/releases/tag/1.1.2).

## [2.2.1]

* Opdaterede til [OS2Forms CPR Lookup
  1.8.0](https://github.com/itk-dev/os2forms_cpr_lookup/releases/tag/1.8.0).
* Opdaterede til [OS2Forms CVR Lookup
  1.4.0](https://github.com/itk-dev/os2forms_cvr_lookup/releases/tag/1.4.0).

## [2.2.0]

* Opdaterede til [OS2Forms GetOrganized
  1.1.1](https://github.com/OS2Forms/os2forms_get_organized/releases/tag/1.1.1)
* Opdaterede `Content Translation` modulets konfiguration for at give
  `Selvbetjeningsdesigner` rollen adgang til oversættelse.
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/184>)
* Opdaterede CPR-opslagskonfiguration
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/182>)
* Opdaterede til [OS2Forms Organisation
  1.1.1](https://github.com/itk-dev/os2forms_organisation/releases/tag/1.1.1)
  med søgning.
* Opdaterede til [OS2Forms Digital post
  1.2.3](https://github.com/itk-dev/os2forms_digital_post/releases/tag/1.2.3).

## [2.1.0]

* os2forms/os2forms_get_organized (1.1.0)
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/177>).
* Opdaterede `itk-dev/os2forms_nemlogin_openid_connect`, `os2forms/os2forms` og
  `os2forms/os2forms_get_organized`
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/176>).
* Installerede `os2forms/os2forms_get_organized`
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/174>).
* Opdaterede CVR-elementer og konfiguration af CVR-opslagservice
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/172>).
* Opdaterede CPR- og CVR-opslagservicer og konfiguration af samme
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/168>).
* Installerede FBS-modulet
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/171>).
* Fiksede tjek for webformulargrupper
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/166>).
* Updaterede os2web/os2web_datalookup
  (<https://github.com/OS2web/os2web_datalookup/compare/1.5.1...1.5.2>).
* Fiksede Xdebug-opsætning.
* Fiksede GitHub Action til at installere site.
* Fjernede “custom” `os2forms_user_field_lookup`-modul.
* Opdaterede “config ignore”-regler
* Tilføjet handler til oprettelse af bruger i FBS med guardian.
* os2forms_failed_jobs (1.1.0)
* itk-dev/itkdev-booking (1.0.1)
* Tilføjet language switcher block
* Updated composite element styling
* Added translation block in top

## [2.0.0] 29.03.2023

* Gjorde det muligt at installere sitet forfra og opdaterede
  installationsvejledning.

### Opdateret

* Hide scrollbar on sidebar navigation
* Opdaterede os2forms/os2forms_rest_api
* os2forms_digital_post (1.2.0)
* Remove dompdf
* Opdaterede docker compose-setup.
* Disable IP tracking
* Deny anonymous access to webform node revisions
* Allow access to config translations for user role forloeb_designer
* Remove revisions tab
* Håndtering af os2web/os2web_nemlogin konfiguration.
* Disable caching on failed jobs list.
* itk-dev/getorganized-api-client-php (1.2.0)
* Udvidet GetOrganized handler med funktionalitet
  til at arkivere vedhæftede filer som bilag i GO.
* Konfiguration ændringer
  * Aktiverede `Signature` og `Email confirm` webform elementer.
  * Brug af `daemon` til GetOrganized og REST API queues.
  * Sortede maestro flows efter `id` på `/maestro-all-flows`.
* Tilføjede mulighed for konfigration af webform category
* Opdaterede os2forms/os2forms_forloeb og drupal/ultimate_cron
* Fjernede `user_default_page`
* Tilføjede `os2forms_attachment`

## [1.7.1] 24.02.2023

* Behandl digital post-køer via rigtig [`cron`](https://en.wikipedia.org/wiki/Cron).

## [1.7.0] 13.02.2023

### Tilføjet

* Installerede [OS2Forms organisation
  1.0.0](https://github.com/itk-dev/os2forms_organisation/releases/tag/1.0.0).
* Phpstan config

### Opdateret

* os2forms_failed_jobs (1.0.1)
* os2forms_webform_submission_log (1.0.0)
* os2forms_digital_post (1.1.2)
* Danske oversættelser

## 06.02.2023

### Tilføjet

* Understøttelse af Næste generation Digital Post (NgDP)
  ([SF1601](https://digitaliseringskataloget.dk/integration/sf1601)) i
  webformularhandler.
* GitHub Action-tjek for at `CHANGELOG.md` er opdateret.
* Tilføjet at sider (node) med adgang anonym kan besøges af alle anonyme brugere
  og drupal-brugere selvom de ikke har rettigheder til siden (node).
* Tilføjet Book aarhus.
* Tilføj conditions til "Mere"-element
* Tilføjet Api request handler logging.
* Tilføjet get organized handler logging.
* Add handler id to errors.
* Opdaterede til [OS2Forms CPR Lookup
  1.7.0](https://github.com/itk-dev/os2forms_cpr_lookup/releases/tag/1.7.0).
* Opdaterede til [OS2Forms CVR Lookup
  1.3.0](https://github.com/itk-dev/os2forms_cvr_lookup/releases/tag/1.3.0).
* Installerede [OS2Forms sync
  1.0.0](https://github.com/itk-dev/os2forms_sync/releases/tag/1.0.0).
* Opgraderede til [PHP 8.1](https://www.php.net/releases/8.1/en.php).
* Installerede [OS2Forms Failed jobs
  1.0.0](https://github.com/itk-dev/os2forms_failed_jobs/releases/tag/1.0.0).
* Tilføjede danske oversættelser.
* Tilføjede patch
  <https://www.drupal.org/files/issues/2022-03-15/content_entity_revisions-3260602-07.patch>.

### Fix

* Begrænse workflow dropdown til bruger <https://github.com/itk-dev/os2forms_selvbetjening/pull/113>
* Unpublished indhold kan publiceres igen. <https://github.com/itk-dev/os2forms_selvbetjening/pull/112>
* Changed spacing in header and footer. <https://github.com/itk-dev/os2forms_selvbetjening/pull/121>

### Fjernet

* Fjerne handlers som ikke virker

## 14.12.2022

### Tilføjet

* Patch så sektionsoverskrifter kan fjernes fra submissions

### Fix

* CPR børn bugfix for sektioner

## 8.12.2022

### Tilføjet

* Logs på submissions
* Valg-liste: Likert: mindre til mere

## 29.11.2022

### Tilføjet

* Valg med magistratsafdelinger
* {Empty} oversat til {Tom}
* Nemlogin-logud knap flyttes til user menu modul
* Nemlogin-navn visning og håndtering hvis navn ikke er tilgængelig

### Fjernet

* Nemlogin-logud meddelelse deaktiveret

## 27.10.2022

### Tilføjet

* Genvejsknapper aktiveret
* Synlig linje om testmiljø eller lignende introduceret
* Selvbetjeningsdesigner fået adgang til redigering af formular-kilde (yml)

## 14.10.2022

### Tilføjet

* Computed twig aktiveret
* Book Aarhus modul

## 16.09.2022

### Tilføjet

* GO borgersager

[Under udvikling]: https://github.com/itk-dev/os2forms_selvbetjening/compare/4.6.7...HEAD
[4.6.7]: https://github.com/itk-dev/os2forms_selvbetjening/compare/4.6.6...4.6.7
[4.6.6]: https://github.com/itk-dev/os2forms_selvbetjening/compare/4.6.5...4.6.6
[4.6.5]: https://github.com/itk-dev/os2forms_selvbetjening/compare/4.6.4...4.6.5
[4.6.4]: https://github.com/itk-dev/os2forms_selvbetjening/compare/4.6.3...4.6.4
[4.6.3]: https://github.com/itk-dev/os2forms_selvbetjening/compare/4.6.2...4.6.3
[4.6.2]: https://github.com/itk-dev/os2forms_selvbetjening/compare/4.6.1...4.6.2
[4.6.1]: https://github.com/itk-dev/os2forms_selvbetjening/compare/4.6.0...4.6.1
[4.6.0]: https://github.com/itk-dev/os2forms_selvbetjening/compare/4.5.0...4.6.0
[4.5.0]: https://github.com/itk-dev/os2forms_selvbetjening/compare/4.4.0...4.5.0
[4.4.0]: https://github.com/itk-dev/os2forms_selvbetjening/compare/4.3.0...4.4.0
[4.3.0]: https://github.com/itk-dev/os2forms_selvbetjening/compare/4.2.1...4.3.0
[4.2.1]: https://github.com/itk-dev/os2forms_selvbetjening/compare/4.2.0...4.2.1
[4.2.0]: https://github.com/itk-dev/os2forms_selvbetjening/compare/4.1.0...4.2.0
[4.1.0]: https://github.com/itk-dev/os2forms_selvbetjening/compare/4.0.1...4.1.0
[4.0.1]: https://github.com/itk-dev/os2forms_selvbetjening/compare/4.0.0...4.0.1
[4.0.0]: https://github.com/itk-dev/os2forms_selvbetjening/compare/3.2.10...4.0.0
[3.2.10]: https://github.com/itk-dev/os2forms_selvbetjening/compare/3.2.9...3.2.10
[3.2.9]: https://github.com/itk-dev/os2forms_selvbetjening/compare/3.2.8...3.2.9
[3.2.8]: https://github.com/itk-dev/os2forms_selvbetjening/compare/3.2.7...3.2.8
[3.2.7]: https://github.com/itk-dev/os2forms_selvbetjening/compare/3.2.6...3.2.7
[3.2.6]: https://github.com/itk-dev/os2forms_selvbetjening/compare/3.2.5...3.2.6
[3.2.5]: https://github.com/itk-dev/os2forms_selvbetjening/compare/3.2.4...3.2.5
[3.2.4]: https://github.com/itk-dev/os2forms_selvbetjening/compare/3.2.3...3.2.4
[3.2.3]: https://github.com/itk-dev/os2forms_selvbetjening/compare/3.2.2...3.2.3
[3.2.2]: https://github.com/itk-dev/os2forms_selvbetjening/compare/3.2.1...3.2.2
[3.2.1]: https://github.com/itk-dev/os2forms_selvbetjening/compare/3.2.0...3.2.1
[3.2.0]: https://github.com/itk-dev/os2forms_selvbetjening/compare/3.1.1...3.2.0
[3.1.1]: https://github.com/itk-dev/os2forms_selvbetjening/compare/3.1.0...3.1.1
[3.1.0]: https://github.com/itk-dev/os2forms_selvbetjening/compare/3.0.1...3.1.0
[3.0.1]: https://github.com/itk-dev/os2forms_selvbetjening/compare/3.0.0...3.0.1
[3.0.0]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.8.3...3.0.0
[2.8.3]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.8.2...2.8.3
[2.8.2]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.8.1...2.8.2
[2.8.1]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.8.0...2.8.1
[2.8.0]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.7.14...2.8.0
[2.7.14]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.7.13...2.7.14
[2.7.13]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.7.12...2.7.13
[2.7.12]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.7.11...2.7.12
[2.7.11]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.7.10...2.7.11
[2.7.10]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.7.9...2.7.10
[2.7.9]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.7.8...2.7.9
[2.7.8]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.7.7...2.7.8
[2.7.7]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.7.6...2.7.7
[2.7.6]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.7.5...2.7.6
[2.7.5]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.7.4...2.7.5
[2.7.4]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.7.3...2.7.4
[2.7.3]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.7.2...2.7.3
[2.7.2]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.7.1...2.7.2
[2.7.1]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.7.0...2.7.1
[2.7.0]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.6.8...2.7.0
[2.6.8]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.6.7...2.6.8
[2.6.7]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.6.6...2.6.7
[2.6.6]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.6.5...2.6.6
[2.6.5]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.6.4...2.6.5
[2.6.4]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.6.3...2.6.4
[2.6.3]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.6.2...2.6.3
[2.6.2]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.6.1...2.6.2
[2.6.1]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.6.0...2.6.1
[2.6.0]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.5.0...2.6.0
[2.5.0]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.4.9...2.5.0
[2.4.9]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.4.8...2.4.9
[2.4.8]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.4.7...2.4.8
[2.4.7]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.4.6...2.4.7
[2.4.6]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.4.5...2.4.6
[2.4.5]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.4.4...2.4.5
[2.4.4]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.4.3...2.4.4
[2.4.3]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.4.2...2.4.3
[2.4.2]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.4.1...2.4.2
[2.4.1]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.4.0...2.4.1
[2.4.0]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.3.1...2.4.0
[2.3.1]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.3.0...2.3.1
[2.3.0]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.2.1...2.3.0
[2.2.1]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.2.0...2.2.1
[2.2.0]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.1.0...2.2.0
[2.1.0]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.0.0...2.1.0
[2.0.0]: https://github.com/itk-dev/os2forms_selvbetjening/compare/1.7.1...2.0.0
[1.7.1]: https://github.com/itk-dev/os2forms_selvbetjening/compare/1.7.0...1.7.1
[1.7.0]: https://github.com/itk-dev/os2forms_selvbetjening/releases/tag/1.7.0
