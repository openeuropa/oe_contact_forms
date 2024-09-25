<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_contact_forms\Functional;

use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\contact\Entity\ContactForm;
use Drupal\contact\Entity\Message;

/**
 * Tests removal of contact messages.
 *
 * @see \Drupal\oe_contact_forms\Plugin\views\field\ContactMessageRemainingDays.
 */
class RemainingDaysTest extends ViewTestBase {

  use CronRunTrait;

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
   * {@inheritdoc}
   * // phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
   */
  protected function setUp($import_test_views = TRUE, $modules = ['oe_contact_forms_test_views']): void {
    parent::setUp($import_test_views, $modules);
  }

  /**
   * Tests Remaining days column.
   */
  public function testRemainingDaysColumn(): void {
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
    $this->config('contact.settings')->set('auto_delete', 4)->save();

    // The messages are not deleted yet as we don't have yet expired messages.
    $message_storage = \Drupal::entityTypeManager()->getStorage('contact_message');
    $this->assertCount(3, $message_storage->loadMultiple());
    $this->cronRun();
    $this->assertCount(3, $message_storage->loadMultiple());
    $this->drupalGet('/test-messages-list');
    $index = 3;
    foreach ($this->getSession()->getPage()->findAll('css', 'table tr td.views-field-remaining-days') as $cell) {
      $this->assertEquals($index . ($index === 1 ? ' day' : ' days'), $cell->getText());
      $index--;
    }

    // Change the auto delete days to 2.
    $this->config('contact.settings')->set('auto_delete', 2)->save();
    // Wait at lest for 2 seconds to make sure the messages are expired.
    sleep(2);
    $this->cronRun();
    // Left only 1 message which has not expired yet.
    $this->assertCount(1, $message_storage->loadMultiple());
  }

  /**
   * Tests auto delete config field.
   */
  public function testAutoDeleteConfigField(): void {
    $admin_account = $this->drupalCreateUser(['administer contact forms']);
    $this->drupalLogin($admin_account);
    $this->drupalGet('/admin/structure/contact/settings');
    $this->assertSession()->fieldExists('auto_delete');
    $this->assertSession()->fieldValueEquals('auto_delete', "0");
    $this->assertEquals('Set the number of days after which the contact messages will be automatically deleted. Set to 0 to disable.', $this->getSession()->getPage()->find('css', '#edit-auto-delete--description')->getText());
    $this->getSession()->getPage()->fillField('auto_delete', 5);
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertSession()->fieldValueEquals('auto_delete', "5");
  }

}
