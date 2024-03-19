<?php

namespace Drupal\Tests\oe_contact_forms\Functional;

use Drupal\contact\Entity\ContactForm;
use Drupal\contact\Entity\Message;
use Drupal\Tests\BrowserHtmlDebugTrait;
use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Tests the contact link field.
 *
 * @group contact
 * @see \Drupal\oe_contact_forms\Plugin\views\field\ContactMessageRemainingDays.
 */
class RemainingDaysTest extends ViewTestBase {

  use BrowserHtmlDebugTrait;

  /**
   * Stores the user data service used by the test.
   *
   * @var \Drupal\user\UserDataInterface
   */
  public $userData;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['oe_contact_forms', 'oe_contact_forms_test_views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['contact_messages_test'];

  /**
   * Tests Remaining days column.
   */
  public function testRemainingDaysColumn() {
    // Prepare a corporate contact form.
    $contact_form = ContactForm::create(['id' => 'oe_contact_form']);
    $contact_form->save();

    foreach (range(1, 3) as $i) {
      // Create a sample message.
      $message = Message::create([
        'id' => $i,
        'contact_form' => $contact_form->id(),
        'created' => time() - ((60 * 60 * 24) * $i),
        'subject' => 'Test subject ' . $i,
      ]);
      $message->save();
    }

    $admin_account = $this->drupalCreateUser(['view contact messages']);
    $this->drupalLogin($admin_account);
    $this->drupalGet('/test-messages-list');
    $this->assertEquals('Remaining days', $this->getSession()->getPage()->find('css', 'table tr th#view-remaining-days-table-column')->getText());
    foreach ($this->getSession()->getPage()->findAll('css', 'table tr td.views-field-remaining-days') as $cell) {
      $this->assertEmpty($cell->getText());
    }
    $this->config('contact.settings')->set('auto_delete', 3)->save();

    $this->getSession()->reload();
    $index = 2;
    foreach ($this->getSession()->getPage()->findAll('css', 'table tr td.views-field-remaining-days') as $cell) {
      $this->assertEquals($index--, $cell->getText());
    }
  }

}
