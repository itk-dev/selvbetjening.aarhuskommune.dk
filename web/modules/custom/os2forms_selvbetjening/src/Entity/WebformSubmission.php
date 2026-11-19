<?php

namespace Drupal\os2forms_selvbetjening\Entity;

use Drupal\webform\Entity\WebformSubmission as BaseWebformSubmission;

/**
 * Custom webform submission class.
 *
 * The base webform submission class uses a weird static property to get a
 * submission's webform, i.e. we may end up in a situation where ALL submissions
 * report having the same webform.
 *
 * It turns out that the webform_revisions module handled this for us, but that
 * module has other issues and are therefore no longer installed.
 *
 * @see EntityTypeHooks::entityTypeAlter()
 */
class WebformSubmission extends BaseWebformSubmission {

  /**
   * {@inheritdoc}
   *
   * NOTE: The parent method can return the webform set in the static $webform
   * property. We don't want that!
   *
   * @see parent::getWebform()
   * @see parent::$webform
   */
  public function getWebform() {
    return $this->webform_id->entity;

    // Maybe we could/shou;d throw an exception if we don't have a webform, i.e
    // use something like
    // .
    // @code
    //    $webform = $this->webform_id->entity ?? null;
    //    if (null === $webform || !$webform instanceof WebformInterface) {
    //      $message = sprintf('Webform not set on submission %s', $this->id());
    //      throw new \RuntimeException($message);
    //    }
    //
    //    return $webform;
    // @endcode
  }

}
