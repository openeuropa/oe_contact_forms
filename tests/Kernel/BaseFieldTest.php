<?php

declare(strict_types = 1);

use Drupal\Tests\BrowserTestBase;
use Drupal\contact\Entity\ContactForm;
use Drupal\contact\Entity\Message;

/**
 * Tests contact form with new fields.
 */
class BaseFieldTest extends BrowserTestBase {

  /**
   * An test user with permission to submit contact forms.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testuser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'contact',
    'contact_storage',
    'oe_contact_forms',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create and login test user.
    $this->testuser = $this->drupalCreateUser([
      'access site-wide contact form',
    ]);
    $this->drupalLogin($this->testuser);
  }

  /**
   * Tests the field validation for corporate forms.
   */
  public function testCorporateFormFieldValidation(): void {
    $contact_form_id = 'oe_contact_form';
    $contact_form = ContactForm::create(['id' => $contact_form_id]);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->save();

    $this->drupalGet('contact/' . $contact_form_id);
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      'subject[0][value]' => 'Test subject',
      'message[0][value]' => 'Test message',
    ];

    $this->drupalPostForm('contact/' . $contact_form_id, $edit, t('Send message'));
    $this->assertText(t('@name field is required.', ['@name' => 'Topic']));
  }

  /**
   * Tests successful corporate forms message.
   */
  public function testCorporateFormBaseFields(): void {
    $contact_form_id = 'oe_contact_form';
    $contact_form = ContactForm::create(['id' => $contact_form_id]);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topics', ['test']);
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
    $data['oe_country_residence'] = 'http://publications.europa.eu/resource/authority/country/BEL';
    $data['oe_telephone'] = '0123456';
    $data['oe_topic'] = 0;

    $message = Message::create($data);
    $message->save();
    $message = Message::load(1);

    $this->assertEquals($data['oe_country_residence'], $message->get('oe_country_residence')->getValue()['0']['target_id']);
    $this->assertEquals($data['oe_telephone'], $message->get('oe_telephone')->getValue()['0']['value']);
    $this->assertEquals($data['oe_topic'], $message->get('oe_topic')->getValue()['0']['value']);
  }

  /**
   * Tests the field absence for default forms.
   */
  public function testDefaultFormFieldAbsence(): void {
    $contact_form_id = 'default_form';
    $contact_form = ContactForm::create(['id' => $contact_form_id]);
    $contact_form->save();

    $this->drupalGet('contact/' . $contact_form_id);
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      'subject[0][value]' => 'Test subject',
      'message[0][value]' => 'Test message',
    ];

    $this->drupalPostForm('contact/' . $contact_form_id, $edit, t('Send message'));
    $this->assertNoText(t('@name field is required.', ['@name' => 'Topic']));
  }

}
