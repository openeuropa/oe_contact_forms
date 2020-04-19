<?php

declare(strict_types = 1);

namespace Drupal\oe_contact_forms\Controller;

use Drupal\contact\ContactFormInterface;
use Drupal\contact_storage\Controller\ContactStorageController;

/**
 * Controller routines for contact storage routes.
 */
class OeContactFormsController extends ContactStorageController {

  /**
   * {@inheritdoc}
   */
  public function contactSitePage(ContactFormInterface $contact_form = NULL) {
    $config = $this->config('contact.settings');
    // If the requested contact form is corporate,
    // render it with the corporate form handler ("corporate_default"),
    // instead of the default one.
    if (!empty($contact_form) && $contact_form->getThirdPartySetting('oe_contact_forms', 'is_corporate_form', FALSE)) {
      $message = $this->entityTypeManager()->getStorage('contact_message')->create([
        'contact_form' => $contact_form->id(),
      ]);

      $form = $this->entityFormBuilder()->getForm($message, 'corporate_default');
      $form['#title'] = $contact_form->label();
      $form['#cache']['contexts'][] = 'user.permissions';
      $this->renderer->addCacheableDependency($form, $config);

      return $form;
    }

    return parent::contactSitePage($contact_form);
  }

}
