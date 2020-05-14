<?php

declare(strict_types = 1);

namespace Drupal\oe_contact_forms\Controller;

use Drupal\contact\ContactFormInterface;
use Drupal\contact_storage\Controller\ContactStorageController;

/**
 * Controller for corporate contact form canonical URLs.
 */
class CorporateContactFormController extends ContactStorageController {

  /**
   * {@inheritdoc}
   */
  public function contactSitePage(ContactFormInterface $contact_form = NULL): array {
    // Check contact form and it's not corporate, return the parent call.
    if (empty($contact_form) || !$contact_form->getThirdPartySetting('oe_contact_forms', 'is_corporate_form', FALSE)) {
      return parent::contactSitePage($contact_form);
    }
    // If the requested contact form is corporate,
    // render it with the corporate form handler ("corporate_default"),
    // instead of the default one.
    /** @var \Drupal\contact\MessageInterface $contact_message */
    $message = $this->entityTypeManager()->getStorage('contact_message')->create([
      'contact_form' => $contact_form->id(),
    ]);

    $form = $this->entityFormBuilder()->getForm($message, 'corporate_default');
    $config = $this->config('contact.settings');
    $this->renderer->addCacheableDependency($form, $config);

    return $form;
  }

}
