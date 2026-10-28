# Config Entity Revisions (local copy)

This directory is a copy of the contrib module
[drupal/config_entity_revisions](https://www.drupal.org/project/config_entity_revisions).
It is kept in `web/modules/custom/` and is no longer installed by Composer.

The module's own documentation, describing what it does and how to add
revision support for another config entity type, is the unchanged upstream
README in [docs/README.md](docs/README.md).

## Scheduled for removal

Both modules are uninstalled by
`os2forms_selvbetjening_post_update_remove_webform_revisions()`, which also
deletes all stored revisions. Delete this directory in the release after that
hook has run on every environment.

## Why

The module is effectively unmaintained upstream: the last commit on the
`2.0.x` branch is from 2024-02-28, there is no stable release, and Drupal 11
compatibility has not been declared. On Drupal 10 the site used the
`2.0.x-dev` package through `mglaman/composer-drupal-lenient` together with a
set of Composer patches. With the upgrade to Drupal 11 the module fails
fatally, so the code was copied into this project on 2026-10-26 to be
maintained here. The patches are now applied directly in the source.

## Upstream base

- Project: <https://www.drupal.org/project/config_entity_revisions>
- Repository: <https://git.drupalcode.org/project/config_entity_revisions.git>
- Branch: `2.0.x`
- Commit: `06017f2dcbaed95b4d0475b06173630833fa8239` (2024-02-28,
  "ConfigEntityRevisionsRevertFormBase constructor.")

Upstream also has a `2.x` branch. It is older, has diverged from `2.0.x`,
and lacks the Drupal 10 compatibility work and several files this site
depends on. Do not use it as a base for comparisons or updates.

## Local changes compared to the upstream commit

Changes 1 to 3 were previously Composer patches in
`patches/drupal/config_entity_revisions/` and are now part of the code.

1. `modules/webform_revisions/src/Entity/WebformRevisionsSubmission.php`:
   added the missing `use Drupal\Core\Serialization\Yaml;` import.

2. `modules/webform_revisions/webform_revisions.install`:
   `webform_revisions_install()` only creates the `webform_revisions`
   config entity revisions type if it does not already exist, because the
   hook can run twice during `drush site:install --existing-config`. The
   obsolete update hook `webform_revisions_8000()` was also removed.

3. `src/ConfigEntityRevisionsConfigTrait.php`:
   `__wakeup()` now declares a `: void` return type. Drupal core's
   `DependencySerializationTrait`, used by `EntityBase`, declares
   `__wakeup(): void`.

4. Swiitched modules from `^9 || ^10` to `^10 || ^11`.
   (Not a former patch; made directly in this copy on 2026-10-26.)

Everything else is identical to the upstream commit, apart from the
documentation: upstream's `README.md` was moved to `docs/README.md` and
replaced by this file.

## Maintaining this copy

- Make further changes directly in this directory and add them to the list
  above, so the copy can still be compared with upstream.
- To diff against upstream, clone the repository, check out the commit above
  and run `diff -r` against this directory. The only expected differences
  besides the list above are this `README.md` and the `docs/` directory.
