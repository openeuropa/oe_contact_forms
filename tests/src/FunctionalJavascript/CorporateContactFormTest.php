<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\FunctionalJavascript;

use Drupal\contact\Entity\ContactForm;
use Drupal\contact\Entity\Message;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;

/**
 * Tests the corporate contact forms.
 */
class CorporateContactFormTest extends WebDriverTestBase {

  /**
   * Corporate fields to test against.
   *
   * @var array
   */
  protected $fields = [
    'corporate_fields[topic_label]' => 'Topic label',
    'corporate_fields[email_subject]' => 'Email subject',
    'corporate_fields[header]' => 'Header text',
    'corporate_fields[privacy_policy]' => 'http://example.net',
    'corporate_fields[includes_fields_in_auto_reply]' => TRUE,
    'corporate_fields[allow_canonical_url]' => TRUE,
    // For expose_as_block default is true so we test false.
    'corporate_fields[expose_as_block]' => FALSE,
    'corporate_fields[optional_fields][oe_country_residence]' => TRUE,
    'corporate_fields[optional_fields][oe_telephone]' => TRUE,
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'user',
    'path',
    'node',
    'oe_contact_forms',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create and login test user with permission to create contact forms.
    /** @var \Drupal\user\UserInterface $test_user */
    $test_user = $this->drupalCreateUser([
      'administer contact forms',
    ]);
    $this->drupalLogin($test_user);
  }

  /**
   * Test export of a corporate contact form.
   */
  public function testCorporateContactFormExport(): void {
    $this->drupalLogout();

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

  /**
   * Tests the corporate contact form.
   */
  public function testCorporateContactForm(): void {
    /** @var \Behat\Mink\WebAssert $assert */
    $assert = $this->assertSession();
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    $this->drupalGet('admin/structure/contact/add');

    // Assert contact_storage_disabled_form_message is visible.
    $disabled_message = $page->findField('contact_storage_disabled_form_message');
    $this->assertTrue($disabled_message->isVisible());

    // Assert corporate fields are not present before is_corporate_form click.
    $is_corporate_form = $page->findField('is_corporate_form');
    $this->assertNotEmpty($is_corporate_form);
    $this->assertFieldsVisible(FALSE);

    // Ajax call to load corporate fields.
    $is_corporate_form->click();
    $assert->assertWaitOnAjaxRequest();

    // Assert contact_storage_disabled_form_message was hidden.
    $this->assertFalse($disabled_message->isVisible());

    // Assert fields are now visible.
    $this->assertFieldsVisible(TRUE);

    // Assert expose_as_block is checked by default.
    $element = $page->findField('corporate_fields[expose_as_block]');
    $this->assertNotEmpty($element);
    $this->assertTrue($element->isChecked());

    // Add contact required values.
    $this->fillCoreContactFields();

    // Test topic ajax.
    $this->assertTopicAjax();

    // Add remaining field values and submit form.
    $this->fillCorporateFields();
    $page->pressButton('Save');
    $assert->pageTextContains('Contact form Test form has been added.');

    // Assert the values are saved.
    $this->drupalGet('admin/structure/contact/manage/oe_corporate_form');
    $is_corporate_form = $page->findField('is_corporate_form');
    $this->assertNotEmpty($is_corporate_form);
    $this->assertTrue($is_corporate_form->isChecked());

    // Assert contact_storage_disabled_form_message is hidden.
    $disabled_message = $page->findField('contact_storage_disabled_form_message');
    $this->assertFalse($disabled_message->isVisible());

    // Make sure the saved values are the ones expected.
    $this->checkCorporateFieldsOnPage();
    $this->checkCorporateFieldsInStorage();

    // Add more topic values, retest ajax.
    $this->assertTopicAjax(1);

    // Go through the edit process.
    $page->pressButton('Save');
    $assert->pageTextContains('Contact form Test form has been updated.');

    // Assert the values are saved.
    $this->drupalGet('admin/structure/contact/manage/oe_corporate_form');

    // Make sure the saved values are the ones expected.
    $this->checkCorporateFieldsOnPage(2);
    $this->checkCorporateFieldsInStorage(2);

    // Assert internal links for privacy policy.
    $field_name = 'corporate_fields[privacy_policy]';
    $element = $page->findField($field_name);
    $value = '<front>';
    $element->setValue($value);
    $page->pressButton('Save');
    $assert->pageTextContains('Contact form Test form has been updated.');

    // Make sure the saved value is the one expected.
    $this->drupalGet('admin/structure/contact/manage/oe_corporate_form');
    $element = $page->findField($field_name);
    $this->assertNotEmpty($element);
    $this->assertEquals($value, $element->getValue());

    // Test with entity reference.
    $alias = '/privacy-page';
    $node = Node::create([
      'title' => 'Privacy page',
      'type' => 'page',
      'path' => ['alias' => $alias],
      'status' => TRUE,
      'uid' => 0,
    ]);
    $node->save();
    $value = 'Privacy page (' . $node->id() . ')';
    $element->setValue($value);
    $page->pressButton('Save');
    $assert->pageTextContains('Contact form Test form has been updated.');

    $this->drupalGet('admin/structure/contact/manage/oe_corporate_form');
    $element = $page->findField($field_name);
    $this->assertNotEmpty($element);
    $this->assertEquals($value, $element->getValue());

    // Test with node alias.
    $element->setValue($alias);
    $page->pressButton('Save');
    $assert->pageTextContains('Contact form Test form has been updated.');

    $this->drupalGet('admin/structure/contact/manage/oe_corporate_form');
    $element = $page->findField($field_name);
    $this->assertNotEmpty($element);
    $this->assertEquals($alias, $element->getValue());
  }

  /**
   * Test corporate values are not set on default form.
   */
  public function testNoCorporateValues(): void {
    /** @var \Behat\Mink\WebAssert $assert */
    $assert = $this->assertSession();
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    $this->drupalGet('admin/structure/contact/add');

    // Assert corporate fields are not present.
    $is_corporate_form = $page->findField('is_corporate_form');
    $this->assertNotEmpty($is_corporate_form);
    $this->assertFieldsVisible(FALSE);

    // Ajax call to load corporate fields.
    $is_corporate_form->click();
    $assert->assertWaitOnAjaxRequest();

    // Check that fields are now created.
    $this->assertFieldsVisible(TRUE);

    // Click again to remove them.
    $is_corporate_form->click();
    $assert->assertWaitOnAjaxRequest();

    // Check that fields are now removed.
    $this->assertFieldsVisible(FALSE);

    // Add contact required values.
    $this->fillCoreContactFields();

    $page->pressButton('Save');
    $assert->pageTextContains('Contact form Test form has been added.');

    // Assert the values are saved.
    $this->drupalGet('admin/structure/contact/manage/oe_corporate_form');
    $this->assertFieldsVisible(FALSE);
    $this->checkCorporateFieldsNotInStorage();
  }

  /**
   * Test transition from corporate to default form.
   */
  public function testCorporateDefaultTransition(): void {
    /** @var \Behat\Mink\WebAssert $assert */
    $assert = $this->assertSession();
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    $this->drupalGet('admin/structure/contact/add');

    // Ajax call to load corporate fields.
    $is_corporate_form = $page->findField('is_corporate_form');
    $this->assertNotEmpty($is_corporate_form);
    $is_corporate_form->click();
    $assert->assertWaitOnAjaxRequest();

    // Assert fields are now visible.
    $this->assertFieldsVisible(TRUE);

    // Add contact required values.
    $this->fillCoreContactFields();

    // Test topic ajax.
    $this->assertTopicAjax();

    // Add remaining field values and submit form.
    $this->fillCorporateFields();
    $page->pressButton('Save');
    $assert->pageTextContains('Contact form Test form has been added.');

    // Assert the values are saved.
    $this->drupalGet('admin/structure/contact/manage/oe_corporate_form');
    $is_corporate_form = $page->findField('is_corporate_form');
    $this->assertNotEmpty($is_corporate_form);
    $this->assertTrue($is_corporate_form->isChecked());

    // Make sure the saved values are the ones expected.
    $this->checkCorporateFieldsOnPage();
    $this->checkCorporateFieldsInStorage();

    // Now we transition to default form.
    $is_corporate_form->click();
    $assert->assertWaitOnAjaxRequest();

    // Assert fields are now removed.
    $this->assertFieldsVisible(FALSE);

    $page->pressButton('Save');
    $assert->pageTextContains('Contact form Test form has been updated.');

    // Assert no corporate values are saved.
    $this->drupalGet('admin/structure/contact/manage/oe_corporate_form');
    $this->assertFieldsVisible(FALSE);
    $this->checkCorporateFieldsNotInStorage();
  }

  /**
   * Helper to assert fields are missing.
   *
   * @var boolean $presence
   */
  protected function assertFieldsVisible($presence): void {
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    foreach ($this->fields as $field_name => $value) {
      $element = $page->findField($field_name);
      if ($presence) {
        $this->assertNotEmpty($element);
        $this->assertTrue($element->isVisible());
      }
      else {
        $this->assertEmpty($element);
      }
    }
  }

  /**
   * Set mandatory core contact fields.
   */
  protected function fillCoreContactFields(): void {
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    $page->fillField('label', 'Test form');
    $page->fillField('recipients', 'test@example.com');
    // Overcome machine name not being accessible.
    $this->getSession()->executeScript('jQuery("#edit-id").val("oe_corporate_form");');
  }

  /**
   * Trigger topic add ajax and assert new fields.
   *
   * @param int $delta
   *   The group index.
   */
  protected function assertTopicAjax($delta = 0): void {
    /** @var \Behat\Mink\WebAssert $assert */
    $assert = $this->assertSession();
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    $topic_name_key = "corporate_fields[topics_fieldset][group][$delta][topic_name]";
    $topic_email_key = "corporate_fields[topics_fieldset][group][$delta][topic_email_address]";

    $page->fillField($topic_name_key, 'topic-' . $delta);
    $page->fillField($topic_email_key, $delta . '-topic@email.com');

    // Test add topic group.
    $add_topic = $page->find('css', 'input[value="Add topic"]');
    $this->assertNotEmpty($add_topic);
    $add_topic->click();
    $assert->assertWaitOnAjaxRequest();

    $delta++;
    $topic_name_key = "corporate_fields[topics_fieldset][group][$delta][topic_name]";
    $topic_email_key = "corporate_fields[topics_fieldset][group][$delta][topic_email_address]";

    $element = $page->findField($topic_name_key);
    $this->assertNotEmpty($element);
    $element = $page->findField($topic_email_key);
    $this->assertNotEmpty($element);

    // Assert fields are required.
    $page->fillField($topic_name_key, '');
    $page->fillField($topic_email_key, '');
    $add_topic->click();
    $assert->assertWaitOnAjaxRequest();
    $assert->fieldExists($topic_name_key)->hasClass('error');
    $assert->fieldExists($topic_email_key)->hasClass('error');
    $assert->pageTextContains('Topic name field is required.');
    $assert->pageTextContains('Topic email address(es) field is required.');

    // Assert email validaiton.
    $page->fillField($topic_email_key, 'not an email');
    $add_topic->click();
    $assert->assertWaitOnAjaxRequest();
    $assert->fieldExists($topic_email_key)->hasClass('error');
    $assert->pageTextContains('is an invalid email address.');

    // Add valid second row of values.
    $page->fillField($topic_name_key, 'topic-' . $delta);
    $page->fillField($topic_email_key, $delta . '-topic@email.com');
    $add_topic->click();
    $assert->assertWaitOnAjaxRequest();

    $delta++;
    $topic_name_key = "corporate_fields[topics_fieldset][group][$delta][topic_name]";
    $topic_email_key = "corporate_fields[topics_fieldset][group][$delta][topic_email_address]";

    $element = $page->findField($topic_name_key);
    $this->assertNotEmpty($element);
    $element = $page->findField($topic_email_key);
    $this->assertNotEmpty($element);

    // Test remove topic group.
    $remove_topic = $page->find('css', 'input[value="Remove topic"]');
    $this->assertNotEmpty($remove_topic);
    $remove_topic->click();
    $assert->assertWaitOnAjaxRequest();

    $element = $page->findField($topic_name_key);
    $this->assertEmpty($element);
    $element = $page->findField($topic_email_key);
    $this->assertEmpty($element);

    // Assert previous two are still there.
    $delta--;
    $topic_name_key = "corporate_fields[topics_fieldset][group][$delta][topic_name]";
    $topic_email_key = "corporate_fields[topics_fieldset][group][$delta][topic_email_address]";

    $element = $page->findField($topic_name_key);
    $this->assertNotEmpty($element);
    $element = $page->findField($topic_email_key);
    $this->assertNotEmpty($element);
  }

  /**
   * Add test values to corporate fields.
   */
  protected function fillCorporateFields(): void {
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    foreach ($this->fields as $field_name => $value) {
      $element = $page->findField($field_name);

      if ($value === TRUE) {
        $element->check();
      }
      elseif ($value === FALSE) {
        $element->uncheck();
      }
      else {
        $element->setValue($value);
      }
    }
  }

  /**
   * Check that the values saved are the ones expected.
   *
   * @param int $max_delta
   *   The max topic group index.
   */
  protected function checkCorporateFieldsOnPage($max_delta = 1): void {
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    // Dynamically add topic fields.
    for ($i = 0; $i <= $max_delta; $i++) {
      $this->fields["corporate_fields[topics_fieldset][group][$i][topic_name]"] = 'topic-' . $i;
      $this->fields["corporate_fields[topics_fieldset][group][$i][topic_email_address]"] = $i . '-topic@email.com';
    }

    foreach ($this->fields as $field_name => $value) {
      $element = $page->findField($field_name);
      $this->assertNotEmpty($element);

      // Assert value on page.
      if ($value === TRUE) {
        $this->assertTrue($element->isChecked());
      }
      elseif ($value === FALSE) {
        $this->assertFalse($element->isChecked());
      }
      else {
        $this->assertEquals($value, $element->getValue());
      }
    }
  }

  /**
   * Check that the values saved in storage are the ones expected.
   *
   * @param int $max_delta
   *   The max topic group index.
   */
  protected function checkCorporateFieldsInStorage($max_delta = 1): void {
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $entity_storage */
    $entity_storage = \Drupal::entityTypeManager()->getStorage('contact_form');
    /** @var \Drupal\contact\ContactFormInterface $contact_form */
    $contact_form = $entity_storage->load('oe_corporate_form');
    $this->assertNotEmpty($contact_form);

    $expected_values = [
      'is_corporate_form' => TRUE,
      'topic_label' => 'Topic label',
      'email_subject' => 'Email subject',
      'header' => 'Header text',
      'privacy_policy' => 'http://example.net',
      'includes_fields_in_auto_reply' => TRUE,
      'allow_canonical_url' => TRUE,
      'expose_as_block' => FALSE,
      'optional_fields' => ['oe_country_residence' => 'oe_country_residence', 'oe_telephone' => 'oe_telephone'],
      'topics' => [],
    ];

    // Dynamically add topic fields.
    for ($i = 0; $i <= $max_delta; $i++) {
      $expected_values['topics'][] = ['topic_name' => 'topic-' . $i, 'topic_email_address' => $i . '-topic@email.com'];
    }

    foreach ($expected_values as $key => $expected) {
      $value = $contact_form->getThirdPartySetting('oe_contact_forms', $key);
      $this->assertEquals($expected, $value);
    }
  }

  /**
   * Check that the values saved in storage are the ones expected.
   */
  protected function checkCorporateFieldsNotInStorage(): void {
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $entity_storage */
    $entity_storage = \Drupal::entityTypeManager()->getStorage('contact_form');
    /** @var \Drupal\contact\ContactFormInterface $contact_form */
    $contact_form = $entity_storage->load('oe_corporate_form');
    $this->assertNotEmpty($contact_form);

    $expected_values = [
      'is_corporate_form' => FALSE,
      'topic_label' => NULL,
      'email_subject' => NULL,
      'header' => NULL,
      'privacy_policy' => NULL,
      'includes_fields_in_auto_reply' => NULL,
      'allow_canonical_url' => NULL,
      'expose_as_block' => NULL,
      'optional_fields' => NULL,
      'topics' => NULL,
    ];

    foreach ($expected_values as $key => $expected) {
      $value = $contact_form->getThirdPartySetting('oe_contact_forms', $key);
      $this->assertEquals($expected, $value);
    }
  }

}
