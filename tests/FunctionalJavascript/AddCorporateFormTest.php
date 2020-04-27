<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test Thirdparty settings on contact forms.
 */
class AddCorporateFormTest extends WebDriverTestBase {

  /**
   * An test user with permission to create contact forms.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testuser;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'oe_contact_forms',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create and login test user.
    $this->testuser = $this->drupalCreateUser([
      'administer contact forms',
    ]);
    $this->drupalLogin($this->testuser);
  }

  /**
   * Tests the corporate field requirements.
   *
   * First we test requirements for state dependent fields,
   * then we test the ajax add and remove,
   * finally we submit and see if values are saved.
   */
  public function testAddCorporateForm(): void {
    $this->drupalGet('admin/structure/contact/add');

    /** @var \Behat\Mink\WebAssert $assert */
    $assert = $this->assertSession();
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();
    $elements = [];
    $fields = [
      'topics_fieldset[group][0][topic_name]' => 'Topic name',
      'topics_fieldset[group][0][topic_email_address]' => 'topic@emailaddress.com',
      'topic_label' => 'Topic label',
      'email_subject' => 'Email subject',
      'header' => 'Header text',
      'privacy_policy' => 'Privacy text',
      'allow_canonical_url' => TRUE,
      // For expose_as_block default is true so we test false.
      'expose_as_block' => FALSE,
      'optional_fields[oe_country_residence]' => TRUE,
      'optional_fields[oe_telephone]' => TRUE,
    ];

    foreach ($fields as $field_name => $value) {
      // Assert new form fields are hidden at first.
      $elements[$field_name] = $page->findField($field_name);
      $this->assertNotEmpty($elements[$field_name]);
      $this->assertFalse($elements[$field_name]->isVisible());
    }

    // Assert elements are now visible.
    $is_corporate_form = $page->findField('is_corporate_form');
    $this->assertNotEmpty($is_corporate_form);
    $is_corporate_form->click();

    foreach ($fields as $field_name => $value) {
      // Assert new form fields are hidden at first.
      $this->assertTrue($elements[$field_name]->isVisible());
    }

    // Assert includes_fields_in_auto_reply is still false,
    // this element also depends on core auto-reply field.
    $includes_fields_in_auto_reply = $page->findField('includes_fields_in_auto_reply');
    $this->assertNotEmpty($includes_fields_in_auto_reply);
    $this->assertFalse($includes_fields_in_auto_reply->isVisible());
    $page->fillField('reply[value]', 'Test reply text');
    $this->assertTrue($includes_fields_in_auto_reply->isVisible());

    // Assert expose_as_block is checked by default.
    $this->assertTrue($elements['expose_as_block']->isChecked());

    // Add contact required values.
    $page->fillField('label', 'Corporate form');
    $page->fillField('recipients', 'test@example.com');
    // Overcome machine name not accessible.
    $this->getSession()->executeScript('jQuery("#edit-id").val("oe_corporate_form");');

    // Test ajax.
    $elements['topics_fieldset[group][0][topic_name]']->setValue($fields['topics_fieldset[group][0][topic_name]']);
    $elements['topics_fieldset[group][0][topic_email_address]']->setValue($fields['topics_fieldset[group][0][topic_email_address]']);

    // Test add topic group.
    $add_topic = $page->find('css', 'input[value="Add topic"]');
    $this->assertNotEmpty($add_topic);
    $add_topic->click();
    $assert->assertWaitOnAjaxRequest();
    $topic_name2 = $page->findField('topics_fieldset[group][1][topic_name]');
    $this->assertNotEmpty($topic_name2);
    $topic_email_address2 = $page->findField('topics_fieldset[group][1][topic_email_address]');
    $this->assertNotEmpty($topic_email_address2);

    // Test remove topic group.
    $remove_topic = $page->find('css', 'input[value="Remove topic"]');
    $this->assertNotEmpty($remove_topic);
    $remove_topic->click();
    $assert->assertWaitOnAjaxRequest();
    $topic_name2 = $page->findField('topics_fieldset[group][1][topic_name]');
    $this->assertEmpty($topic_name2);
    $topic_email_address2 = $page->findField('topics_fieldset[group][1][topic_email_address]');
    $this->assertEmpty($topic_email_address2);

    // Add remaining field values and submit form.
    foreach ($fields as $field_name => $value) {
      if ($value === TRUE) {
        $elements[$field_name]->check();
      }
      elseif ($value === FALSE) {
        $elements[$field_name]->uncheck();
      }
      else {
        $elements[$field_name]->setValue($value);
      }
    }

    // Yes for autoreply.
    $includes_fields_in_auto_reply->check();

    $page->pressButton('Save');
    $assert->pageTextContains('Contact form Corporate form has been added.');

    // Amazing, now assert the values are saved.
    $this->drupalGet('admin/structure/contact/manage/oe_corporate_form');
    $is_corporate_form = $page->findField('is_corporate_form');
    $this->assertNotEmpty($is_corporate_form);
    $this->assertTrue($is_corporate_form->isChecked());

    foreach ($fields as $field_name => $value) {
      $element = $page->findField($field_name);
      $this->assertNotEmpty($element);

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

    $includes_fields_in_auto_reply = $page->findField('includes_fields_in_auto_reply');
    $this->assertNotEmpty($includes_fields_in_auto_reply);
    $this->assertTrue($includes_fields_in_auto_reply->isChecked());
  }

}
