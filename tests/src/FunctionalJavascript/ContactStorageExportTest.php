<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\contact\Entity\ContactForm;
use Drupal\contact\Entity\Message;

/**
 * Test Contact Storage Export form, corporate contact forms.
 */
class ContactStorageExportTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'file',
    'contact',
    'contact_storage',
    'oe_contact_forms',
  ];

  /**
   * Tests that export of corporate form does not contain IP.
   */
  public function testCorporateForm(): void {
    /** @var \Behat\Mink\WebAssert $assert */
    $assert = $this->assertSession();
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    // Prepare a corporate contact form.
    $contact_form_id = 'oe_contact_form';
    $contact_form = ContactForm::create(['id' => $contact_form_id]);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->save();

    // Create a sample message.
    $message = Message::create([
      'id' => 1,
      'contact_form' => $contact_form->id(),
      'name' => 'example',
      'mail' => 'admin@example.com',
      'created' => '1487321550',
      'ip_address' => '127.0.0.1',
      'subject' => 'Test subject',
      'message' => 'Test message',
    ]);
    $message->save();

    $account = $this->drupalCreateUser([
      'access administration pages',
      'access site-wide contact form',
      'administer contact forms',
      'export contact form messages',
    ]);
    $this->drupalLogin($account);

    // Check the form is correct.
    $this->drupalGet('admin/structure/contact/manage/export', ['query' => ['contact_form' => $contact_form_id]]);
    $assert->elementNotExists('css', '#edit-columns-ip-address');
    $field_ids = [
      'edit-columns-id',
      'edit-columns-langcode',
      'edit-columns-contact-form',
      'edit-columns-name',
      'edit-columns-mail',
      'edit-columns-subject',
      'edit-columns-message',
      'edit-columns-copy',
      'edit-columns-recipient',
      'edit-columns-created',
      'edit-columns-uid',
      'edit-columns-oe-country-residence',
      'edit-columns-oe-telephone',
      'edit-columns-oe-telephone',
    ];

    foreach ($field_ids as $field_id) {
      $assert->checkboxChecked($field_id);
    }

    $page->pressButton('Export');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Assert CSV output with selected columns.
    /* @var \Drupal\file\FileInterface[] $files */
    $files = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties(['filename' => 'contact-storage-export.csv']);
    $file = reset($files);
    $absolute_path = \Drupal::service('file_system')->realpath($file->getFileUri());
    $actual = file_get_contents($absolute_path);

    $headers = '"Message ID",Language,"Form ID","The sender\'s name","The sender\'s email",Subject,Message,Copy,"Recipient ID",Created,"User ID","Country of residence",Phone,Topic';
    $values = '1,English,,example,admin@example.com,"Test subject","Test message",,,"Fri, 02/17/2017 - 19:52",0,,,';
    $expected = $headers . PHP_EOL . $values;
    $this->assertEquals($expected, $actual);
  }

}
