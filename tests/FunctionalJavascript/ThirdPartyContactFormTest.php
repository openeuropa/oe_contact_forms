<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test Thirdparty settings on contact forms.
 */
class ThirdPartyContactFormTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'seven';

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
    'oe_contact_forms',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create and login test user.
    $this->testuser = $this->drupalCreateUser([
      'view the administration theme',
      'administer contact forms',
    ]);
    $this->drupalLogin($this->testuser);
  }

  /**
   * Tests the contact add form.
   */
  public function testAddForm(): void {
    $this->drupalGet(Url::fromRoute('contact.form_add'));

    /** @var \Behat\Mink\WebAssert $assert */
    $assert = $this->assertSession();
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
    $assert->waitForElementVisible('css', '.admin-link .link', 1);
    $this->createScreenshot('/var/www/html/AAA.png');
    $edit = $page->find('css', '.admin-link .link');
    $this->assertNotEmpty($edit);
    $this->assertTrue($edit->isVisible());
    $edit->click();
    $page->fillField('id', 'oe_corporate_form');
    $page->fillField('recipients', 'test@example.com');

    // Test ajax.
    $topic_name->setValue('First option');
    $topic_email_address->setValue('first@email.com');
    $add_topic = $page->find('css', 'input[value="Add topic"]');
    $this->assertNotEmpty($add_topic);
    $add_topic->click();
    $assert->assertWaitOnAjaxRequest();
    $this->createScreenshot('/var/www/html/AAA.png');
    // $page->pressButton('Add more');
    // $page->pressButton('Save');
  }

}
