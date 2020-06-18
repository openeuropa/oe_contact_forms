<?php

declare(strict_types = 1);

namespace Drupal\oe_contact_forms\Routing;

use Symfony\Component\Routing\RouteCollection;
use Drupal\contact_storage\Routing\RouteSubscriber;
use Drupal\oe_contact_forms\Controller\CorporateContactFormController;

/**
 * Listens to the dynamic route events.
 */
class ContactFormRouteSubscriber extends RouteSubscriber {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection): void {
    parent::alterRoutes($collection);

    if ($route = $collection->get('entity.contact_form.canonical')) {
      // Change the contact_form controller.
      $route->setDefault('_controller', CorporateContactFormController::class . '::contactSitePage');
      // Add access check.
      $route->setRequirements([
        '_oe_contact_forms_access_check' => 'TRUE',
        '_entity_access' => 'contact_form.view',
      ]);
    }
  }

}
