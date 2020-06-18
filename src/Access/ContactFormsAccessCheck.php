<?php

declare(strict_types = 1);

namespace Drupal\oe_contact_forms\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityAccessCheck;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Route access handler for contact form routes.
 */
class ContactFormsAccessCheck extends EntityAccessCheck {

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $contact_form = $route_match->getParameters()->get('contact_form');
    $cache = new CacheableMetadata();
    $cache->addCacheableDependency($contact_form);
    $cache->addCacheContexts(['route']);

    // Deny access if form is corporate and the 'Allow canonical URL' is FALSE,
    // unless user is contact forms administrator.
    if (
      !empty($contact_form) &&
      $contact_form->getThirdPartySetting('oe_contact_forms', 'is_corporate_form', FALSE) &&
      !$contact_form->getThirdPartySetting('oe_contact_forms', 'allow_canonical_url', FALSE) &&
      !$account->hasPermission('administer contact forms')
    ) {
      return AccessResult::forbidden()->addCacheableDependency($cache);
    }
    // Deny if form is corporate and the 'Allow canonical URL' is TRUE and
    // the user does not have 'access corporate contact form' permission.
    if (
      $contact_form->getThirdPartySetting('oe_contact_forms', 'is_corporate_form', FALSE) &&
      $contact_form->getThirdPartySetting('oe_contact_forms', 'allow_canonical_url', FALSE) &&
      !$account->hasPermission('access corporate contact form')
    ) {
      return AccessResult::forbidden()->addCacheableDependency($cache);
    }

    // No opinion, so parent access checks should decide if access should be
    // allowed or not.
    return parent::access($route, $route_match, $account);
  }

}
