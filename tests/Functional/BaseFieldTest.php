<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\contact\Entity\ContactForm;

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
