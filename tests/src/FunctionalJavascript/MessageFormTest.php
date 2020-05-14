<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\contact\Entity\ContactForm;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Test\AssertMailTrait;

/**
 * Test Corporate MessageForm behaviour.
 */
class MessageFormTest extends WebDriverTestBase {

  use AssertMailTrait {
    getMails as drupalGetMails;
  }

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
    /** @var \Drupal\user\UserInterface $test_user */
    $testuser = $this->drupalCreateUser([
      'access site-wide contact form',
    ]);
    $this->drupalLogin($testuser);
  }

  /**
   * Tests for corporate forms behaviour.
   */
  public function testCorporateForm(): void {
    /** @var \Behat\Mink\WebAssert $assert */
    $assert = $this->assertSession();
    // Prepare a corporate contact form.
    $contact_form_id = 'oe_contact_form';
    $contact_form = ContactForm::create(['id' => $contact_form_id]);
    $contact_form->setReply('this is a autoreply');
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $topic_label = 'Topic label';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topic_label', $topic_label);
    $email_subject = 'Email Subject';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'email_subject', $email_subject);
    $header = 'this is a test header';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'header', $header);
    $privacy_text = 'this is a test privacy policy';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'privacy_policy', $privacy_text);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'includes_fields_in_auto_reply', TRUE);
    $optional_selected = ['oe_country_residence' => 'oe_country_residence'];
    $contact_form->setThirdPartySetting('oe_contact_forms', 'optional_fields', $optional_selected);
    $topics = [['topic_name' => 'Topic name', 'topic_email_address' => 'topic@emailaddress.com']];
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topics', $topics);
    $contact_form->save();

    // Access canonical url.
    $this->drupalGet('contact/' . $contact_form_id);

    // Assert corporate fields and optional fields handling.
    $assert->fieldExists('oe_country_residence[0][target_id]');
    $assert->fieldNotExists('oe_telephone[0][value]');
    $assert->fieldExists('oe_topic');
    $assert->fieldExists('privacy_policy');
    // Assert header printed.
    $assert->pageTextContains($header);
    // Assert privacy text.
    $assert->pageTextContains($privacy_text);
    // Assert topic label.
    $assert->elementTextContains('css', 'label[for="edit-oe-topic"]', $topic_label);
    // Assert elements order.
    $elements = $this->xpath('//form');
    $this->assertCount(1, $elements);
    // TODO: is there a better method for this ?
    $html = $elements['0']->getOuterHtml();
    $i = [];
    $i['header'] = Unicode::strpos($html, $header);
    $i['name'] = Unicode::strpos($html, 'edit-name');
    $i['email'] = Unicode::strpos($html, 'edit-mail');
    $i['subject'] = Unicode::strpos($html, 'edit-subject-0-value');
    $i['message'] = Unicode::strpos($html, 'edit-message-0-value');
    $i['topic'] = Unicode::strpos($html, 'edit-oe-topic');
    $i['country'] = Unicode::strpos($html, 'edit-oe-country-residence-0-target-id');
    $i['privacy'] = Unicode::strpos($html, 'edit-privacy-policy');

    $this->assertTrue($i['header'] < $i['name']);
    $this->assertTrue($i['name'] < $i['email']);
    $this->assertTrue($i['email'] < $i['subject']);
    $this->assertTrue($i['subject'] < $i['message']);
    $this->assertTrue($i['message'] < $i['topic']);
    $this->assertTrue($i['topic'] < $i['country']);
    $this->assertTrue($i['country'] < $i['privacy']);

    // Submit the form.
    $page = $this->getSession()->getPage();
    $page->fillField('subject[0][value]', 'Test subject');
    $page->fillField('message[0][value]', 'Test message');
    $page->selectFieldOption('oe_topic', 'Topic name');
    $page->findField('privacy_policy')->click();
    $page->findButton('Send message')->click();

    // Assert confirmation message.
    $assert->elementTextContains('css', '.messages--status', $privacy_text);
    $assert->elementTextContains('css', '.messages--status', $topics['0']['topic_name']);

    // Load captured emails to check.
    $captured_emails = $this->drupalGetMails();
    $this->assertTrue(count($captured_emails) === 2);

    // Assert email subject.
    $this->assertTrue($captured_emails[0]['subject'] === $email_subject);
    // Assert email recipients.
    $this->assertTrue($captured_emails[0]['to'] === $topics['0']['topic_email_address']);
    // Make sure we have an autoreply.
    $this->assertTrue($captured_emails[1]['id'] === 'contact_page_autoreply');
    // Assert fields in autoreply.
    $this->assertTrue(strpos($captured_emails[1]['body'], 'Test subject') !== FALSE);
    $this->assertTrue(strpos($captured_emails[1]['body'], 'Test message') !== FALSE);
    $this->assertTrue(strpos($captured_emails[1]['body'], $topics['0']['topic_name']) !== FALSE);
  }

  /**
   * Tests default form is not changed.
   */
  public function testDefaultFormNotChanged(): void {
    /** @var \Behat\Mink\WebAssert $assert */
    $assert = $this->assertSession();
    // Prepare a default contact form.
    $contact_form_id = 'default_form';
    $contact_form = ContactForm::create(['id' => $contact_form_id]);
    $contact_form->setRecipients(['test@test.com']);
    $contact_form->setReply('this is a autoreply');
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', FALSE);
    $contact_form->save();

    // Access canonical url.
    $this->drupalGet('contact/' . $contact_form_id);

    // Submit the form.
    $page = $this->getSession()->getPage();
    $page->fillField('subject[0][value]', 'Test subject');
    $page->fillField('message[0][value]', 'Test message');
    $page->findButton('Send message')->click();

    // Assert no confirmation message.
    $assert->elementNotExists('css', '.messages--status');

    // Load captured emails to check.
    $captured_emails = $this->drupalGetMails();
    $this->assertTrue(count($captured_emails) === 2);

    // Make sure we have an autoreply.
    $this->assertTrue($captured_emails[1]['id'] === 'contact_page_autoreply');
    // Assert fields in autoreply.
    $this->assertTrue(strpos($captured_emails[1]['body'], 'Test subject') === FALSE);
    $this->assertTrue(strpos($captured_emails[1]['body'], 'Test message') === FALSE);
  }

}
