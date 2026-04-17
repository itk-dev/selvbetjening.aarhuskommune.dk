# Selvbetjening examples

Webform examples for Selvbetjening.

The basic idea is that we export webforms with IDs matching specific patterns
into the `config/install` folder in sub-modules to group releated webforms.

## Example usage

1. [Install the module](#installation) and [load the base data](#load-base-data)
2. Install the `itkdev_ex_misc` module:

   ``` shell
   drush pm:install itkdev_ex_misc
   ```

3. Create a webform whose ID starts with `itkdev_ex_`, e.g. `itkdev_ex_my_example`
4. Run

   ``` shell
   drush itkdev-example-forms:webforms:export
   ```

   to export the webform into the `itkdev_ex_misc` module's `config/install` folder.
5. Edit
   `web/modules/custom/itkdev_example_forms/modules/itkdev_ex_misc/config/install/webform.webform.itkdev_ex_my_example.yml`
   and update the webform's `title`, say.
6. Run

   ``` shell
   drush itkdev-example-forms:webforms:reset itkdev_ex_my_example
   ```

   to import the updated webform config (cf.
   [`/admin/structure/webform/manage/itkdev_ex_my_example/settings`](https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/itkdev_ex_my_example/settings)
7. Edit the webform on [`/admin/structure/webform/manage/itkdev_ex_my_example/settings`](https://selvbetjening.local.itkdev.dk/da/admin/structure/webform/manage/itkdev_ex_my_example/settings)
8. Run

   ``` shell
   drush itkdev-example-forms:webforms:export
   ```

   to export the updated webform

See [`itkdev_ex_misc.install`](modules/itkdev_ex_misc/itkdev_ex_misc.install)
for examples on how to create content pages for an example webform.

## Installation

``` shell
# Optional
# drush site:install --existing-config --yes
drush pm:install itkdev_example_forms
```

Run

``` shell
drush list --filter=itkdev-example-forms
```

to see a list of Drush commands defined by this module.

## Load base data

Load taxomnomy terms:

``` shell
drush content-fixtures:load --groups=itkdev_example_forms
```

> [!WARNING]
> Loading fixtures will reset *all* your content.

## Load example webforms

Load example webforms by installing an example webforms module, e.g.

``` shell
drush pm:install itkdev_ex_misc
```

Run

``` shell
drush pm:list --package='ITK Dev example forms'
```

to see a list of all example webforms modules.

> [!TIP]
> Uninstalling the module will remove the example webforms loaded by the module.

## Exporting example webforms

``` shell
drush itkdev-example-forms:webforms:export
```

Add `--verbose` to see more info on what is actually done:

``` shell
drush itkdev-example-forms:webforms:export --verbose
```

Use `--yes` to just do it:

``` shell
drush itkdev-example-forms:webforms:export --yes
```

## Resetting example webforms

You can reset a set of example webforms with the follow incantation:

``` shell
drush config:import --source=modules/custom/itkdev_example_forms/modules/itkdev_ex_misc/config/install/ --partial
```

Reset (config for) select webforms with

``` shell
drush itkdev-example-forms:webforms:reset
```
