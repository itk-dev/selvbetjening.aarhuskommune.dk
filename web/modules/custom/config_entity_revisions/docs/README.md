# CONFIG ENTITY REVISIONS

This module provides an API for augmenting Configuration entities in
Drupal 8.5 and later with revision and moderation support.

## TECHNICAL EXPLANATION

Drupal 8 has two types of entities: Configuration entities and
content entities. Configuration entities don't support versioning
or workflow. Webforms 8.x-5.x implements webforms as configuration
entities so, by default, you can't have different versions of a webform
or apply approval processes to changes in webforms. This applies across
the board to configuration entities - views, menus and other configuration
entities are also affected.

This module began life as work to implement revisioning and moderation
support for webforms by sitting a content entity alongside the config
entity, having it store a serialised version of the config entity data,
and applying revision and moderation workflows to the content entity.

Along the way, it occurred to the author that perhaps there will be other
config entities for which revisioning and workflow will be useful, so the
generic parts of the code have been split out into this module. If you'd
like to implement revisions and moderation for another configuration
entity, you can follow the pattern in the webform_revisions submodule,
which should be pretty straight forward.

## TODO LIST FOR IMPLEMENTING REVISIONS FOR A NEW CONFIG ENTITY

[ ] Review the module you're wanting to extend, noting:
    Module Machine Name: _____________________________
    Config Entity Machine Name: _____________________________
    Config Entity Parameter Placeholder: _____________________________
    Admin Routes:
    - Add form: _____________________________
    - Edit form: _____________________________
    - Delete form: _____________________________
    - Your proposed revisions path and route name: _____________________________
    - Your proposed revision view, revert and delete paths and route names:
      _____________________________
    - Proposed permissions needed to view and manage revisions
    - Any route callbacks you might also need to extend / override
      (Are there content entities related to the config entity that will need
      to have revision IDs recorded and get the associated config entity
      revision loaded when the content entity is loaded? - see webform
      submissions).
       _____________________________________________________
       Associated Content Entity Machine Name: __________________________
[ ] Copy an existing implementation module
[ ] Rename files and classes
[ ] Basic submodule info (.info.yml)
[ ] Documentation (ideally do as you complete the following)
[ ] Unit tests (ideally do as you complete the following)
[ ] Update install schema
[ ] Implement install hooks as required
[ ] Implement uninstall hooks
[ ] Override classes in a hook_entity_type_alter
[ ] Menu items and tasks for accessing the revisions overview
[ ] Revision creation
[ ] Revision table
[ ] Parameter Converter for config entity (easier to debug when you have some
    revisions already created)
[ ] Revision Reversion
[ ] Revision Deletion (associated content entities!)
[ ] Workflow integration
[ ] Views Integration

If there's an associated content entity:
[ ] Add form uses currently published config entity revision
[ ] Add form stores the current config entity revision ID
[ ] Edit form loads the matching config entity revision
[ ] View content entity loads appropriate config entity revision
[ ] Deletion of a config entity revision cascade deletes content entities
    after warning (or other handling if more appropriate)

## MAINTAINER

- Nigel Cunningham - <https://www.drupal.org/u/nigel-cunningham>
