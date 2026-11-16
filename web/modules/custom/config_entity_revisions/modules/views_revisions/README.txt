Views Revisions and Workflow
============================

This module provides integration between the Drupal 8.5 version of views
and Drupal's core revision support, utilising the parent module (content
entity revisions).

To use the module, enable it using the normal Drupal processes, then visit
the views pages. The Build section of the admin pages will have a new
Revisions page, and the page in which elements are created will have a new
Revision information section at the bottom.

To also enable moderation of views, enable the Workflow module and visit
its configuration page and modify a workflow so that it is configured to apply
to Views Revisions. (Under "This workflow applies to", click on "Select" in
the "Config Entity Revisions types" row). The moderation state will then be
able to be edited when editing the view.

Note that the "Create new revision" checkbox on the Build page is not ticked by
default. This is because you'll probably not want to create a new revision for
every change to a view, but just when starting to make changes for a new version.
