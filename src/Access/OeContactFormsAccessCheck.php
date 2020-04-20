<?php

declare(strict_types = 1);

namespace Drupal\oe_contact_forms\Access;

use Drupal\contact\ContactFormInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Determines access to contact_form entity.
 */
class OeContactFormsAccessCheck implements AccessInterface {

  /**
   * Checks access to the entity contact_form.
   */
  public function access(ContactFormInterface $contact_form = NULL) {
    // Deny access if form is corporate and the 'Allow canonical URL' is FALSE.
    if (!empty($contact_form)
        && $contact_form->getThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE)
        && $contact_form->getThirdPartySetting('oe_contact_forms', 'allow_canonical_url', FALSE)
      ) {
      return AccessResult::forbidden();
    }
    else {
      return AccessResult::allowed();
    }
  }

}
