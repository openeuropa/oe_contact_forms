<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\contact\Entity\ContactForm;

/**
 * Test Corporate MessageForm behaviour.
 */
class MessageFormTest extends WebDriverTestBase {

  /**
   * An test user with permission to submit contact forms.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testuser;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
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
   * Tests for corporate forms behaviour.
   */
  public function testCorporateForm(): void {
    $contact_form_id = 'oe_contact_form';
    $contact_form = ContactForm::create(['id' => $contact_form_id]);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topic_name', ['test']);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topic_label', 'Topic label');
    $contact_form->save();

    $this->drupalGet('contact/' . $contact_form_id);
    // $this->createScreenshot('/var/www/html/oe_contact_form.png');
    // Assert the corporate fields.
    $this->assertSession()->fieldExists('oe_country_residence[0][target_id]');
    $this->assertSession()->fieldExists('oe_telephone[0][value]');
    $this->assertSession()->fieldExists('oe_topic');
    $this->assertSession()->fieldExists('privacy_policy');
  }

}
