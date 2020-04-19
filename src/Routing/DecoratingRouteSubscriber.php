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
  public function alterRoutes(RouteCollection $collection) {
    parent::alterRoutes($collection);

    // Change the contact_form controller.
    if ($route = $collection->get('entity.contact_form.canonical')) {
      $route->setDefault('_controller', OeContactFormsController::class . '::contactSitePage');
    }
  }

}
