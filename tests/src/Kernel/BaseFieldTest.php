<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\Kernel;

use Drupal\contact\Entity\ContactForm;
use Drupal\contact\Entity\Message;

/**
 * Tests contact form with new fields.
 */
class BaseFieldTest extends ContactFormTestBase {

  /**
   * Tests successful corporate forms message.
   */
  public function testCorporateFormBaseFields(): void {
    $contact_form_id = 'oe_contact_form';
    $contact_form = ContactForm::create(['id' => $contact_form_id]);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topics', [
      [
        'topic_name' => 'Topic name',
        'topic_email_address' => 'topic@emailaddress.com',
      ],
    ]);
    $contact_form->save();

    $data = [
      'id' => 1,
      'contact_form' => $contact_form->id(),
      'name' => 'example',
      'mail' => 'admin@example.com',
      'created' => time(),
      'ip_address' => '127.0.0.1',
      'subject' => 'Test subject',
      'message' => 'Test message',
      // Add an unsupported value.
      'oe_topic' => FALSE,
    ];

    $message = Message::create($data);
    // Validate the field.
    $violations = $message->oe_topic->validate();
    $this->assertTrue($violations->count() > 0);

    // Set correct values.
    $data['oe_country_residence'] = 'http://publications.europa.eu/resource/authority/country/BEL';
    $data['oe_preferred_language'] = 'http://publications.europa.eu/resource/authority/language/SPA';
    $data['oe_alternative_language'] = 'http://publications.europa.eu/resource/authority/language/FRA';
    $data['oe_telephone'] = '0123456';
    $data['oe_topic'] = 'Topic name';

    $message = Message::create($data);
    // Validate the field.
    $violations = $message->oe_topic->validate();
    $this->assertTrue($violations->count() === 0);
    $message->save();

    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $entity_type_manager */
    $entity_type_manager = \Drupal::entityTypeManager()->getStorage('contact_message');
    $entity_type_manager->resetCache();
    /** @var \Drupal\contact\Entity\Message $message */
    $message = $entity_type_manager->load($message->id());

    $this->assertEquals($data['oe_country_residence'], $message->get('oe_country_residence')->getValue()['0']['target_id']);
    $this->assertEquals($data['oe_preferred_language'], $message->get('oe_preferred_language')->getValue()['0']['target_id']);
    $this->assertEquals($data['oe_alternative_language'], $message->get('oe_alternative_language')->getValue()['0']['target_id']);
    $this->assertEquals($data['oe_telephone'], $message->get('oe_telephone')->getValue()['0']['value']);
    $this->assertEquals($data['oe_topic'], $message->get('oe_topic')->getValue()['0']['value']);
  }

  /**
   * Tests that topic fields are not available in default form.
   */
  public function testDefaultForm(): void {
    $contact_form_id = 'default_form';
    $contact_form = ContactForm::create(['id' => $contact_form_id]);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', FALSE);
    $contact_form->save();

    $data = [
      'id' => 1,
      'contact_form' => $contact_form->id(),
      'name' => 'example',
      'mail' => 'admin@example.com',
      'created' => '1487321550',
      'ip_address' => '127.0.0.1',
      'subject' => 'Test subject',
      'message' => 'Test message',
    ];

    $message = Message::create($data);
    $message->save();

    $form = \Drupal::service('entity.form_builder')->getForm($message, 'default');

    $this->assertEquals(FALSE, $form['oe_country_residence']['#access'], 'Check that country is not available.');
    $this->assertEquals(FALSE, $form['oe_preferred_language']['#access'], 'Check that preferred contact language is not available.');
    $this->assertEquals(FALSE, $form['oe_alternative_language']['#access'], 'Check that alternative contact language is not available.');
    $this->assertEquals(FALSE, $form['oe_telephone']['#access'], 'Check that telephone is not available.');
    $this->assertEquals(FALSE, $form['oe_topic']['#access'], 'Check that topic is not available.');
  }

}
