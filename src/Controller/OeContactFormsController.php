<?php

namespace Drupal\oe_contact_forms\Controller;

use Drupal\contact\ContactFormInterface;
use Drupal\contact\Controller\ContactController;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\contact_storage\Controller\ContactStorageController;

/**
 * Controller routines for contact storage routes.
 */
class OeContactFormsController extends ContactStorageController {

  /**
   * {@inheritdoc}
   */
  public function contactSitePage(ContactFormInterface $contact_form = NULL) {
    // If the requested contact form is corporate,
    // render it with the corporate form handler ("corporate_default"),
    // instead of the default one. 
    if (!empty($contact_form) && $contact_form->getThirdPartySetting('oe_contact_forms', 'is_corporate_form', FALSE)) {
      $contact_form->setHandlerClass('corporate_default', '\Drupal\oe_contact_forms\...');
      $manager = $this->entityTypeManager();
      $view_builder = $manager->getViewBuilder('contact_form');

      return $view_builder->view($contact_form, 'corporate_default', $contact_form->language());
    }

    return parent::contactSitePage($contact_form);
  }

}
