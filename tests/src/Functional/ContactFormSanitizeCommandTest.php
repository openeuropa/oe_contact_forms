<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\Functional;

use Drupal\contact\Entity\ContactForm;
use Drupal\contact\Entity\Message;
use Drupal\Tests\BrowserTestBase;
use Drush\Drush;
use Drush\TestTraits\DrushTestTrait;

/**
 * Tests the message fields drush sanitization.
 */
class ContactFormSanitizeCommandTest extends BrowserTestBase {

  use DrushTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_contact_forms',
  ];

  /**
   * Tests the drush sanitization.
   */
  public function testContactFormMessageDataSanitization() {
    $contact_form_id = 'oe_contact_form';
    $contact_form = ContactForm::create(['id' => $contact_form_id]);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topics', [
      [
        'topic_name' => 'Topic name1',
        'topic_email_address' => 'topic1@emailaddress.com',
      ],
      [
        'topic_name' => 'Topic name2',
        'topic_email_address' => 'topic2@emailaddress.com',
      ],
    ]);
    $contact_form->setRecipients([
      'recipient1@emailaddress.com',
      'recipient2@emailaddress.com',
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

    $this->drush('sql:sanitize');
    \Drupal::configFactory()->clearStaticCache();
    $expected = 'The following operations will be performed:' . PHP_EOL;
    // An extra newline is added when the command is executed with Drupal 9.x.
    // @todo Remove when support for Drupal 9.x is dropped.
    $expected .= version_compare(\Drupal::VERSION, '10.0.0', '<') ? PHP_EOL : '';
    $expected .= '* Truncate sessions table.' . PHP_EOL;
    $expected .= '* Sanitize text fields associated with users.' . PHP_EOL;
    $expected .= '* Sanitize user passwords.' . PHP_EOL;
    $expected .= '* Sanitize user emails.' . PHP_EOL;
    if (Drush::getMajorVersion() >= 12) {
      $expected .= '* Preserve user emails and passwords for the specified roles.' . PHP_EOL;
    }
    $expected .= '* Sanitize contact form data.';
    $this->assertOutputEquals($expected);

    $contact_form_sanitized = ContactForm::load($contact_form_id);
    $topics = $contact_form_sanitized->getThirdPartySetting('oe_contact_forms', 'topics', []);
    foreach ($topics as $key => $topic) {
      $this->assertEquals('topic+' . $key . '@example.com', $topic['topic_email_address']);
    }
    $recipients = $contact_form_sanitized->getRecipients();
    foreach ($recipients as $key => $recipient) {
      $this->assertEquals('recipient+' . $key . '@example.com', $recipient);
    }

    $sanitized_message = Message::load($message_id);

    $this->assertEquals('User' . $message_id, $sanitized_message->get('name')->value);
    $this->assertEquals('user+' . $message_id . '@example.com', $sanitized_message->get('mail')->value);
    $this->assertEquals('subject-by-' . $message_id, $sanitized_message->get('subject')->value);
    $this->assertEquals('message-by-' . $message_id, $sanitized_message->get('message')->value);
    $this->assertEquals('127.0.0.1', $sanitized_message->get('ip_address')->value);
    $this->assertEquals('residence-in-' . $message_id, $sanitized_message->get('oe_country_residence')->target_id);
    $this->assertEquals('+000-' . $message_id, $sanitized_message->get('oe_telephone')->value);
    $this->assertEquals('topic-' . $message_id, $sanitized_message->get('oe_topic')->value);

    $plain_contact_form_sanitized = ContactForm::load($plain_contact_form_id);
    $topics = $plain_contact_form_sanitized->getThirdPartySetting('oe_contact_forms', 'topics', []);
    foreach ($topics as $key => $topic) {
      $this->assertEquals('topic+' . $key . '@example.com', $topic['topic_email_address']);
    }
    $recipients = $plain_contact_form_sanitized->getRecipients();
    foreach ($recipients as $key => $recipient) {
      $this->assertEquals('recipient+' . $key . '@example.com', $recipient);
    }

    $plain_message = Message::load($plain_message_id);

    $this->assertEquals($data['name'], $plain_message->get('name')->value);
    $this->assertEquals($data['mail'], $plain_message->get('mail')->value);
    $this->assertEquals($data['subject'], $plain_message->get('subject')->value);
    $this->assertEquals($data['message'], $plain_message->get('message')->value);
    $this->assertEquals($data['ip_address'], $plain_message->get('ip_address')->value);
  }

}
