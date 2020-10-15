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
      'contact_form' => $contact_form->id(),
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

    $this->assertEqual(1, $message_id);

    /** @var \Drupal\oe_contact_forms\Commands\sql\SanitizeContactFormFieldsCommands $command */
    $command = \Drupal::service('oe_contact_forms.contact.commands');
    $command->sanitize([], $this->createMock(CommandData::class));

    $sanitized_message = Message::load($message_id);

    $residence = $sanitized_message->get('oe_country_residence')->getValue();

    $this->assertEqual('User' . $message_id, $sanitized_message->name->value);
    $this->assertEqual('user+' . $message_id . '@example.com', $sanitized_message->mail->value);
    $this->assertEqual('subject-by-' . $message_id, $sanitized_message->subject->value);
    $this->assertEqual('message-by-' . $message_id, $sanitized_message->message->value);
    $this->assertEqual('127.0.0.1', $sanitized_message->ip_address->value);
    $this->assertEqual('residence-in-' . $message_id, $sanitized_message->oe_country_residence->target_id);
    $this->assertEqual('+000-' . $message_id, $sanitized_message->oe_telephone->value);
    $this->assertEqual('topic-' . $message_id, $sanitized_message->oe_topic->value);
  }

}
