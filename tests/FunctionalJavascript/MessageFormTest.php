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
    $topic_names = ['Agriculture', 'Business and industry'];
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topic_name', $topic_names);
    $topic_emails = ['agri@test.com', 'business@test.com'];
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topic_email_address', $topic_emails);
    $topic_label = 'Topic label';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topic_label', $topic_label);
    $email_subject = 'Email Subject';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'email_subject', $email_subject);
    $header = 'this is a test header';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'header', $header);
    $privacy_text = 'Lorem ipsum sint dolor';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'privacy_policy', $privacy_text);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'includes_fields_in_auto_reply', TRUE);
    $optional_selected = ['oe_country_residence'];
    $contact_form->setThirdPartySetting('oe_contact_forms', 'optional_fields', $optional_selected);
    $contact_form->save();

    // Access canonical url.
    $this->drupalGet('contact/' . $contact_form_id);

    // Assert corporate fields and optional fields handling.
    $this->assertSession()->fieldExists('oe_country_residence[0][target_id]');
    $this->assertSession()->fieldNotExists('oe_telephone[0][value]');
    $this->assertSession()->fieldExists('oe_topic');
    $this->assertSession()->fieldExists('privacy_policy');
    // Assert header printed.
    $this->assertSession()->pageTextContains($header);
    // Assert privacy text.
    $this->assertSession()->pageTextContains($privacy_text);
    // Assert topic label.
    $this->assertSession()->elementTextContains('css', 'label[for="edit-oe-topic"]', $topic_label);
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
    $page->selectFieldOption('oe_topic', '1');
    $page->findField('privacy_policy')->click();
    $page->findButton('Send message')->click();

    // Assert confirmation message.
    $this->assertSession()->elementTextContains('css', '.messages--status', $privacy_text);
    $this->assertSession()->elementTextContains('css', '.messages--status', $email_subject);
    $this->assertSession()->elementTextContains('css', '.messages--status', $topic_names[1]);

    // Load captured emails to check.
    $captured_emails = $this->drupalGetMails();
    $this->assertTrue(count($captured_emails) > 0);

    // Assert email subject.
    $this->assertTrue(strpos($captured_emails[0]['subject'], $email_subject) !== FALSE);
    // Assert email recipients.
    $this->assertTrue($captured_emails[0]['to'] === $topic_emails[1]);
    // Make sure we have an autoreply.
    $this->assertTrue($captured_emails[1]['id'] === 'contact_page_autoreply');
    // Assert fields in autoreply.
    $this->assertTrue(strpos($captured_emails[1]['body'], 'Test subject') !== FALSE);
    $this->assertTrue(strpos($captured_emails[1]['body'], 'Test message') !== FALSE);
    $this->assertTrue(strpos($captured_emails[1]['body'], $topic_names[1]) !== FALSE);
  }

}
