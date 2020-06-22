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
    $contact_form = $route_match->getParameter('contact_form');
    $cache = new CacheableMetadata();
    $cache->addCacheableDependency($contact_form);
    $cache->addCacheContexts(['route']);

    // If not a corporate form, we defer the the original access checker.
    if (!$contact_form->getThirdPartySetting('oe_contact_forms', 'is_corporate_form', FALSE)) {
      return parent::access($route, $route_match, $account)->addCacheableDependency($cache);
    }

    // If the contact form should allow canonical URLs, we defer to the
    // original access checker.
    if (
      $contact_form->getThirdPartySetting('oe_contact_forms', 'allow_canonical_url', FALSE) &&
      $account->hasPermission('access corporate contact form')
    ) {
      return parent::access($route, $route_match, $account)->addCacheableDependency($cache);
    }

    $cache->addCacheContexts(['user.permissions']);

    // Otherwise, we deny access unless the user can actually manage the
    // contact forms.
    if (!$account->hasPermission('administer contact forms')) {
      return AccessResult::forbidden()->addCacheableDependency($cache);
    }

    return parent::access($route, $route_match, $account);
  }

}
