# Selvbetjening examples

Webform examples form Selvbetjening.

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

## Load data and example forms

Load taxomnomy terms:

``` shell
drush content-fixtures:load --groups=itkdev_example_forms
```

> [!WARNING]
> Loading fixtures will reset *all* your content.

Load example forms by installing an example forms module, e.g.

``` shell
drush pm:install itkdev_ex_misc
```

Run

``` shell
drush pm:list --package='ITK Dev example forms'
```

to see a list of all example forms modules.

> [!TIP]
> Uninstalling the module will remove the example forms loaded by the module.

## Exporting example forms

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

## Resetting example forms

You can reset a set of example forms with the follow incantation:

``` shell
drush config:import --source=modules/custom/itkdev_example_forms/modules/itkdev_ex_misc/config/install/ --partial
```

Reset (config for) select webforms with

``` shell
drush itkdev-example-forms:webforms:reset
```
