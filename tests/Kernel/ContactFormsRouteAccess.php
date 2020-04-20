<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\Kernel;

use Drupal\contact\Entity\ContactForm;

/**
 * Tests the Route access.
 */
class ContactFormsRouteAccess extends ContactFormsKernelTestBase {

  /**
   * Tests that entity contact_form is accessible.
   */
  public function testCorporateFormAccess(): void {
    $contact_form_id = 'oe_contact_form';
    $contact_form = ContactForm::create(['id' => $contact_form_id]);
    // Mandatory fields.
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topic_name', ['test']);

    // Set custom property for the test.
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'allow_canonical_url', FALSE);
    $contact_form->save();

    $this->drupalGet($contact_form::url());
    $this->assertResponse(403);

  }

}
