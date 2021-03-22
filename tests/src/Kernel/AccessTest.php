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
    $account1 = $this->createUser(['access site-wide contact form', 'access corporate contact form'], NULL, FALSE, ['name' => 'test1', 'uid' => 2]);
    $this->assertTrue($message_storage->getAccessControlHandler('contact_message')->createAccess($contact_form->id(), $account1));
    // Assert user without "access corporate contact form" permission does not.
    $account2 = $this->createUser(['access site-wide contact form'], NULL, FALSE, ['name' => 'test2', 'uid' => 3]);
    $this->assertFalse($message_storage->getAccessControlHandler('contact_message')->createAccess($contact_form->id(), $account2));

    // Assert form without allow_canonical_url is forbidden.
    $this->assertFalse(Url::fromRoute('entity.contact_form.canonical', [
      'contact_form' => $contact_form->id(),
    ])->access($account1));
    // Assert form without allow_canonical_url is allowed for users with
    // 'administer contact forms' permission.
    $account3 = $this->createUser(['access site-wide contact form', 'administer contact forms'], NULL, FALSE, ['name' => 'test3', 'uid' => 4]);
    $this->assertTrue(Url::fromRoute('entity.contact_form.canonical', [
      'contact_form' => $contact_form->id(),
    ])->access($account3));
    // Assert form with allow_canonical_url is allowed for users with
    // 'access corporate contact form' permission.
    $contact_form = ContactForm::create(['id' => 'oe_contact_form2']);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'allow_canonical_url', TRUE);
    $contact_form->save();
    $this->assertTrue(Url::fromRoute('entity.contact_form.canonical', [
      'contact_form' => $contact_form->id(),
    ])->access($account1));
    // But denied for users without 'access corporate contact form' permission.
    $this->assertFalse(Url::fromRoute('entity.contact_form.canonical', [
      'contact_form' => $contact_form->id(),
    ])->access($account2));

    // Toggle the status of the contact form.
    $contact_form->disable()->save();
    $this->assertFalse(Url::fromRoute('entity.contact_form.canonical', [
      'contact_form' => $contact_form->id(),
    ])->access($account1));
    $contact_form->enable()->save();
    $this->assertTrue(Url::fromRoute('entity.contact_form.canonical', [
      'contact_form' => $contact_form->id(),
    ])->access($account1));

    // Assert the transition of allow_canonical_url.
    $contact_form->setThirdPartySetting('oe_contact_forms', 'allow_canonical_url', FALSE);
    $contact_form->save();
    $this->assertFalse(Url::fromRoute('entity.contact_form.canonical', [
      'contact_form' => $contact_form->id(),
    ])->access($account1));
    $contact_form->setThirdPartySetting('oe_contact_forms', 'allow_canonical_url', TRUE);
    $contact_form->save();
    $this->assertTrue(Url::fromRoute('entity.contact_form.canonical', [
      'contact_form' => $contact_form->id(),
    ])->access($account1));

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
    ])->access($account2));
  }

  /**
   * Tests corporate contact form block access.
   */
  public function testContactBlockAccess(): void {
    /** @var \Drupal\Core\Block\BlockManagerInterface $block_manager */
    $block_manager = $this->container->get('plugin.manager.block');

    $contact_form = ContactForm::create(['id' => 'oe_contact_form']);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'expose_as_block', TRUE);
    $contact_form->save();
    $plugin_id = 'oe_contact_forms_corporate_block:' . $contact_form->uuid();
    $definition = $block_manager->getDefinition($plugin_id);

    // Assert we have the contact form block definition.
    $this->assertEqual($definition['id'], 'oe_contact_forms_corporate_block');

    /** @var \Drupal\Core\Block\BlockPluginInterface $plugin */
    $plugin = $block_manager->createInstance($plugin_id);

    // Assert user with "access corporate contact form" permission has access
    // to contact form block.
    $account1 = $this->createUser(['access corporate contact form'], 'test1');
    $this->assertTrue($plugin->access($account1));
    // Assert user without "access corporate contact form" permission does not.
    $account2 = $this->createUser(['access site-wide contact form'], 'test2');
    $this->assertFalse($plugin->access($account2));

    // Toggle the status of the contact form.
    $contact_form->disable()->save();
    $this->assertFalse($plugin->access($account1));
    $contact_form->enable()->save();
    $this->assertTrue($plugin->access($account1));
  }

}
