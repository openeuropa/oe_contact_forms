<?php

declare(strict_types = 1);

namespace Drupal\oe_contact_forms\Access;

use Drupal\contact\ContactFormInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Access\AccessResultInterface;

/**
 * Route access handler for contact form routes.
 */
class ContactFormsAccessCheck implements AccessInterface {

  /**
   * Denies access to the corporate contact form if not meant for canonical URL.
   *
   * @param Drupal\contact\ContactFormInterface $contact_form
   *   The contact form to check against.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(ContactFormInterface $contact_form = NULL): AccessResultInterface {
    $cache = new CacheableMetadata();
    $cache->addCacheableDependency($contact_form);
    $cache->addCacheContexts(['route']);

    // Deny access if form is corporate and the 'Allow canonical URL' is FALSE.
    if (
      !empty($contact_form) &&
      $contact_form->getThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE) &&
      !$contact_form->getThirdPartySetting('oe_contact_forms', 'allow_canonical_url', FALSE)
    ) {
      return AccessResult::forbidden()->addCacheableDependency($cache);
    }

    return AccessResult::allowed()->addCacheableDependency($cache);
  }

}
