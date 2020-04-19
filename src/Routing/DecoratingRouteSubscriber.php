<?php

declare(strict_types = 1);

namespace Drupal\oe_contact_forms\Routing;

use Symfony\Component\Routing\RouteCollection;
use Drupal\contact_storage\Routing\RouteSubscriber;

/**
 * Listens to the dynamic route events.
 */
class DecoratingRouteSubscriber extends RouteSubscriber {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection): void {
    parent::alterRoutes($collection);

    if ($route = $collection->get('entity.contact_form.canonical')) {
      // Add our custom access check.
      $route->setRequirements([
        '_oe_contact_forms_access_check' => 'TRUE',
      ]);
    }
  }

}
