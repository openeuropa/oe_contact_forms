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

    // Assert new form fields are hidden at first.
    $topic_name = $page->findField('topics_fieldset[group][0][topic_name]');
    $this->assertNotEmpty($topic_name);
    $this->assertFalse($topic_name->isVisible());
    $topic_email_address = $page->findField('topics_fieldset[group][0][topic_email_address]');
    $this->assertNotEmpty($topic_email_address);
    $this->assertFalse($topic_email_address->isVisible());
    $topic_label = $page->findField('topic_label');
    $this->assertNotEmpty($topic_label);
    $this->assertFalse($topic_label->isVisible());
    $email_subject = $page->findField('email_subject');
    $this->assertNotEmpty($email_subject);
    $this->assertFalse($email_subject->isVisible());
    $header = $page->findField('header');
    $this->assertNotEmpty($header);
    $this->assertFalse($header->isVisible());
    $privacy_policy = $page->findField('privacy_policy');
    $this->assertNotEmpty($privacy_policy);
    $this->assertFalse($privacy_policy->isVisible());
    $includes_fields_in_auto_reply = $page->findField('includes_fields_in_auto_reply');
    $this->assertNotEmpty($includes_fields_in_auto_reply);
    $this->assertFalse($includes_fields_in_auto_reply->isVisible());
    $allow_canonical_url = $page->findField('allow_canonical_url');
    $this->assertNotEmpty($allow_canonical_url);
    $this->assertFalse($allow_canonical_url->isVisible());
    $expose_as_block = $page->findField('expose_as_block');
    $this->assertNotEmpty($expose_as_block);
    $this->assertFalse($expose_as_block->isVisible());
    $oe_country_residence = $page->findField('optional_fields[oe_country_residence]');
    $this->assertNotEmpty($oe_country_residence);
    $this->assertFalse($oe_country_residence->isVisible());
    $oe_telephone = $page->findField('optional_fields[oe_telephone]');
    $this->assertNotEmpty($oe_telephone);
    $this->assertFalse($oe_telephone->isVisible());

    // Assert elements are now visible.
    $is_corporate_form = $page->findField('is_corporate_form');
    $this->assertNotEmpty($is_corporate_form);
    $is_corporate_form->click();
    $this->assertTrue($topic_name->isVisible());
    $this->assertTrue($topic_email_address->isVisible());
    $this->assertTrue($topic_label->isVisible());
    $this->assertTrue($email_subject->isVisible());
    $this->assertTrue($header->isVisible());
    $this->assertTrue($privacy_policy->isVisible());
    $this->assertTrue($allow_canonical_url->isVisible());
    $this->assertTrue($expose_as_block->isVisible());
    $this->assertTrue($oe_country_residence->isVisible());
    $this->assertTrue($oe_telephone->isVisible());

    // Assert includes_fields_in_auto_reply is still false,
    // this element also depends on core auto-reply field.
    $this->assertFalse($includes_fields_in_auto_reply->isVisible());
    $page->fillField('reply[value]', 'Test reply text');
    $this->assertTrue($includes_fields_in_auto_reply->isVisible());

    // Assert expose_as_block is checked by default.
    $this->assertTrue($expose_as_block->isChecked());

    // Add contact required values.
    $page->fillField('label', 'Corporate form');
    $page->fillField('recipients', 'test@example.com');
    // Overcome machine name not accessible.
    $this->getSession()->executeScript('jQuery("#edit-id").val("oe_corporate_form");');

    // Test ajax.
    $topic_name->setValue('First option');
    $topic_email_address->setValue('first@email.com');

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
    $topic_label->setValue('Topic label');
    $email_subject->setValue('Email subject');
    $header->setValue('Header text');
    $privacy_policy->setValue('Privacy text');
    $includes_fields_in_auto_reply->check();
    $allow_canonical_url->check();
    // For expose_as_block default is true so we test false.
    $expose_as_block->uncheck();
    $oe_country_residence->check();
    $oe_telephone->check();
    $page->pressButton('Save');
    $assert->pageTextContains('Contact form Corporate form has been added.');

    // Amazing, now assert the values are saved.
    $this->drupalGet('admin/structure/contact/manage/oe_corporate_form');
    $is_corporate_form = $page->findField('is_corporate_form');
    $this->assertNotEmpty($is_corporate_form);
    $this->assertTrue($is_corporate_form->isChecked());
    $topic_name = $page->findField('topics_fieldset[group][0][topic_name]');
    $this->assertNotEmpty($topic_name);
    $this->assertEquals('First option', $topic_name->getValue());
    $topic_email_address = $page->findField('topics_fieldset[group][0][topic_email_address]');
    $this->assertNotEmpty($topic_email_address);
    $this->assertEquals('first@email.com', $topic_email_address->getValue());
    $topic_label = $page->findField('topic_label');
    $this->assertNotEmpty($topic_label);
    $this->assertEquals('Topic label', $topic_label->getValue());
    $email_subject = $page->findField('email_subject');
    $this->assertNotEmpty($email_subject);
    $this->assertEquals('Email subject', $email_subject->getValue());
    $header = $page->findField('header');
    $this->assertNotEmpty($header);
    $this->assertEquals('Header text', $header->getValue());
    $privacy_policy = $page->findField('privacy_policy');
    $this->assertNotEmpty($privacy_policy);
    $this->assertEquals('Privacy text', $privacy_policy->getValue());
    $includes_fields_in_auto_reply = $page->findField('includes_fields_in_auto_reply');
    $this->assertNotEmpty($includes_fields_in_auto_reply);
    $this->assertTrue($includes_fields_in_auto_reply->isChecked());
    $allow_canonical_url = $page->findField('allow_canonical_url');
    $this->assertNotEmpty($allow_canonical_url);
    $this->assertTrue($allow_canonical_url->isChecked());
    $expose_as_block = $page->findField('expose_as_block');
    $this->assertNotEmpty($expose_as_block);
    $this->assertFalse($expose_as_block->isChecked());
    $oe_country_residence = $page->findField('optional_fields[oe_country_residence]');
    $this->assertNotEmpty($oe_country_residence);
    $this->assertTrue($oe_country_residence->isChecked());
    $oe_telephone = $page->findField('optional_fields[oe_telephone]');
    $this->assertNotEmpty($oe_telephone);
    $this->assertTrue($oe_telephone->isChecked());
  }

}
