<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\Kernel;

use Drupal\contact\Entity\ContactForm;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the Route access.
 */
class ContactFormsRouteAccess extends ContactFormsKernelTestBase {

  use UserCreationTrait;

  /**
   * The currently logged in user.
   *
   * @var array
   */
  protected $testUsers;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->testUsers[] = $this->setUpCurrentUser(['name' => 'test1'], ['access corporate contact form']);
    $this->testUsers[] = $this->setUpCurrentUser(['name' => 'test2'], []);
  }

  /**
   * Tests that contact_form is only accessible with specific permissions.
   */
  public function testCorporateFormAccess(): void {
    // Create entity.
    $contact_form = ContactForm::create(['id' => 'oe_contact_form']);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topic_name', ['test']);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'allow_canonical_url', FALSE);
    $contact_form->save();

    $access_handler = $this->container->get('entity_type.manager')->getAccessControlHandler('contact_form');

    // Test users with different permissions.
    foreach ($this->testUsers as $currentuser) {
      // var_dump(get_class_methods($currentuser));
      if ($currentuser->hasPermission('access corporate contact form')) {
        $this->assertTrue($contact_form->access('view', $currentuser));
      }
      else {
        $this->assertFalse($contact_form->access('view', $currentuser));
      }
    }

    $access_handler->resetCache();

  }

}
