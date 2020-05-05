<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

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
    'corporate_fields[topics_fieldset][group][0][topic_name]' => 'Topic name',
    'corporate_fields[topics_fieldset][group][0][topic_email_address]' => 'topic@emailaddress.com',
    'corporate_fields[topic_label]' => 'Topic label',
    'corporate_fields[email_subject]' => 'Email subject',
    'corporate_fields[header]' => 'Header text',
    'corporate_fields[privacy_policy]' => 'Privacy text',
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
    'user',
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
   * Tests the corporate contact form.
   */
  public function testCorporateContactForm(): void {
    /** @var \Behat\Mink\WebAssert $assert */
    $assert = $this->assertSession();
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    $this->drupalGet('admin/structure/contact/add');

    // Assert corporate fields are not present before is_corporate_form click.
    $is_corporate_form = $page->findField('is_corporate_form');
    $this->assertNotEmpty($is_corporate_form);
    $this->assertFieldsPresence(FALSE);

    // Ajax call to load corporate fields.
    $is_corporate_form->click();
    $assert->assertWaitOnAjaxRequest();

    // Assert fields are now visible.
    $this->assertFieldsPresence(TRUE);

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

    // Test topic ajax.
    $this->assertTopicAjax();

    // Make sure the saved values are the ones expected.
    $this->checkCorporateFieldsOnPage();
    $this->checkCorporateFieldsInStorage();
  }

  /**
   * Tests the corporate contact form.
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
    $this->assertFieldsPresence(FALSE);

    // Ajax call to load corporate fields.
    $is_corporate_form->click();
    $assert->assertWaitOnAjaxRequest();

    // Check that fields are now created.
    $this->assertFieldsPresence(TRUE);

    // Click again to remove them.
    $is_corporate_form->click();
    $assert->assertWaitOnAjaxRequest();

    // Check that fields are now created.
    $this->assertFieldsPresence(FALSE);

    // Add contact required values.
    $this->fillCoreContactFields();

    $page->pressButton('Save');
    $assert->pageTextContains('Contact form Test form has been added.');

    // Assert the values are saved.
    $this->drupalGet('admin/structure/contact/manage/oe_corporate_form');
    $this->assertFieldsPresence(FALSE);
    $this->checkCorporateFieldsNotInStorage();
  }

  /**
   * Helper to assert fields are missing.
   *
   * @var boolean $presence
   */
  protected function assertFieldsPresence($presence): void {
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
   */
  protected function assertTopicAjax(): void {
    /** @var \Behat\Mink\WebAssert $assert */
    $assert = $this->assertSession();
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    $topic_name_key = "corporate_fields[topics_fieldset][group][0][topic_name]";
    $topic_email_key = "corporate_fields[topics_fieldset][group][0][topic_email_address]";

    $page->fillField($topic_name_key, $this->fields[$topic_name_key]);
    $page->fillField($topic_email_key, $this->fields[$topic_email_key]);

    // Test add topic group.
    $add_topic = $page->find('css', 'input[value="Add topic"]');
    $this->assertNotEmpty($add_topic);
    $add_topic->click();
    $assert->assertWaitOnAjaxRequest();

    $topic_name_key = "corporate_fields[topics_fieldset][group][1][topic_name]";
    $topic_email_key = "corporate_fields[topics_fieldset][group][1][topic_email_address]";

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
    $page->fillField($topic_name_key, 'Another name');
    $page->fillField($topic_email_key, 'another@emailaddress.com');
    $add_topic->click();
    $assert->assertWaitOnAjaxRequest();

    $topic_name_key = "corporate_fields[topics_fieldset][group][2][topic_name]";
    $topic_email_key = "corporate_fields[topics_fieldset][group][2][topic_email_address]";

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
   */
  protected function checkCorporateFieldsOnPage(): void {
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

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
   */
  protected function checkCorporateFieldsInStorage(): void {
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
      'privacy_policy' => 'Privacy text',
      'includes_fields_in_auto_reply' => TRUE,
      'allow_canonical_url' => TRUE,
      'expose_as_block' => FALSE,
      'optional_fields' => ['oe_country_residence' => 'oe_country_residence', 'oe_telephone' => 'oe_telephone'],
      'topics' => [
        ['topic_name' => 'Topic name', 'topic_email_address' => 'topic@emailaddress.com'],
        ['topic_name' => 'Another name', 'topic_email_address' => 'another@emailaddress.com'],
      ],
    ];

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
      'is_corporate_form' => NULL,
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
