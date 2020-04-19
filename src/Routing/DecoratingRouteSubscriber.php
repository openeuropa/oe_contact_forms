<?php

declare(strict_types = 1);

namespace Drupal\oe_contact_forms\Routing;

use Symfony\Component\Routing\RouteCollection;
use Drupal\contact_storage\Routing\RouteSubscriber;
use Drupal\oe_contact_forms\Controller\OeContactFormsController;

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
      // Change the contact_form controller.
      $route->setDefault('_controller', OeContactFormsController::class . '::contactSitePage');
    }
  }

}
