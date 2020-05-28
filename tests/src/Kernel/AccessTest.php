<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\Kernel;

use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Test the contact form access control.
 */
class AccessTest extends ContactFormTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Define the anonymous user first. User id with 0 has to exist in order
    // to avoid "ContextException: The 'entity:user' context is required and
    // not present" error.
    User::create([
      'name' => 'Anonymous',
      'uid' => 0,
    ])->save();
  }

  /**
   * Tests corporate and default contact form access.
   */
  public function testContactFormAccess(): void {
    /** @var \Drupal\Core\Entity\EntityTypeManager $message_storage */
    $message_storage = $this->container->get('entity_type.manager');

    // Tests corporate contact form access.
    $contact_form = ContactForm::create(['id' => 'oe_contact_form']);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'allow_canonical_url', FALSE);
    $contact_form->save();

    // Assert user with "access corporate contact form" permission has access
    // to contact_message entity.
    $account1 = $this->createUser(['name' => 'test1'], ['access corporate contact form']);
    $this->assertTrue($message_storage->getAccessControlHandler('contact_message')->createAccess($contact_form->id(), $account1));
    // Assert user without "access corporate contact form" permission does not.
    $account2 = $this->createUser(['name' => 'test2'], ['access site-wide contact form']);
    $this->assertFalse($message_storage->getAccessControlHandler('contact_message')->createAccess($contact_form->id(), $account2));

    // Assert form without allow_canonical_url is forbidden.
    $this->assertFalse(Url::fromRoute('entity.contact_form.canonical', [
      'contact_form' => $contact_form->id(),
    ])->access());
    // Assert form with allow_canonical_url is allowed.
    $contact_form = ContactForm::create(['id' => 'oe_contact_form2']);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'allow_canonical_url', TRUE);
    $contact_form->save();
    $this->assertTrue(Url::fromRoute('entity.contact_form.canonical', [
      'contact_form' => $contact_form->id(),
    ])->access());

    // Tests default contact form access.
    $contact_form = ContactForm::create(['id' => 'default_form']);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', FALSE);
    $contact_form->save();

    // Assert user with "access corporate contact form" permission has access
    // to contact_message entity.
    $this->assertTrue($message_storage->getAccessControlHandler('contact_message')->createAccess($contact_form->id(), $account1));
    // Assert user without "access corporate contact form" permission has access
    // to contact_message entity.
    $this->assertTrue($message_storage->getAccessControlHandler('contact_message')->createAccess($contact_form->id(), $account2));

    // Assert we still have access to plain contact form.
    $this->assertTrue(Url::fromRoute('entity.contact_form.canonical', [
      'contact_form' => $contact_form->id(),
    ])->access());
  }

}
