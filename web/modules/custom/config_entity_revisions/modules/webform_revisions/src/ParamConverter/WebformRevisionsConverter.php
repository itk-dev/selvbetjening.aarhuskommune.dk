<?php

namespace Drupal\webform_revisions\ParamConverter;

use Drupal\config_entity_revisions\ConfigEntityRevisionsConverterBase;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\webform_revisions\WebformRevisionsConfigTrait;

/**
 * Parameter converter for upcasting entity IDs to full, revisioned objects.
 *
 * @see entities_revisions_translations
 */
class WebformRevisionsConverter extends ConfigEntityRevisionsConverterBase implements ParamConverterInterface {
  use WebformRevisionsConfigTrait;

}
