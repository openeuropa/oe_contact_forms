<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\Kernel;

use Drupal\contact\Entity\ContactForm;
use Drupal\contact\Entity\Message;
use Consolidation\AnnotatedCommand\CommandData;

/**
 * Tests the message fields drush sanitization.
 */
class SanitizeContactFormFieldsTest extends ContactFormTestBase {

  /**
   * Tests the drush sanitization.
   */
  public function testContactFormMessageDataSanitization() {
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
      'contact_form' => $contact_form_id,
      'name' => 'example',
      'mail' => 'admin@example.com',
      'created' => time(),
      'ip_address' => '8.8.8.8',
      'subject' => 'Test subject',
      'message' => 'Test message',
      'oe_country_residence' => 'http://publications.europa.eu/resource/authority/country/BEL',
      'oe_telephone' => '0123456',
      'oe_topic' => 'Topic name',
    ];

    $message = Message::create($data);
    $message->save();
    $message_id = $message->id();

    $plain_contact_form_id = 'plain_contact_form';
    $plain_contact_form = ContactForm::create(['id' => $plain_contact_form_id]);
    $plain_contact_form->save();

    $data = [
      'contact_form' => $plain_contact_form_id,
      'name' => 'example',
      'mail' => 'admin@example.com',
      'created' => time(),
      'ip_address' => '8.8.8.9',
      'subject' => 'Test subject plain',
      'message' => 'Test message plain',
    ];

    $plain_message = Message::create($data);
    $plain_message->save();
    $plain_message_id = $plain_message->id();

    /** @var \Drupal\oe_contact_forms\Commands\sql\SanitizeContactFormFieldsCommands $command */
    $command = \Drupal::service('oe_contact_forms.contact.sanitize_commands');
    $command->sanitize([], $this->createMock(CommandData::class));

    $sanitized_message = Message::load($message_id);

    $this->assertEqual($sanitized_message->get('name')->value, 'User' . $message_id);
    $this->assertEqual($sanitized_message->get('mail')->value, 'user+' . $message_id . '@example.com');
    $this->assertEqual($sanitized_message->get('subject')->value, 'subject-by-' . $message_id);
    $this->assertEqual($sanitized_message->get('message')->value, 'message-by-' . $message_id);
    $this->assertEqual($sanitized_message->get('ip_address')->value, '127.0.0.1');
    $this->assertEqual($sanitized_message->get('oe_country_residence')->target_id, 'residence-in-' . $message_id);
    $this->assertEqual($sanitized_message->get('oe_telephone')->value, '+000-' . $message_id);
    $this->assertEqual($sanitized_message->get('oe_topic')->value, 'topic-' . $message_id);

    $sanitized_message = Message::load($plain_message_id);

    $this->assertEqual($sanitized_message->get('name')->value, $data['name']);
    $this->assertEqual($sanitized_message->get('mail')->value, $data['mail']);
    $this->assertEqual($sanitized_message->get('subject')->value, $data['subject']);
    $this->assertEqual($sanitized_message->get('message')->value, $data['message']);
    $this->assertEqual($sanitized_message->get('ip_address')->value, $data['ip_address']);
  }

}
