<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\FunctionalJavascript;

use Drupal\contact\Entity\ContactForm;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

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
    'block',
    'path',
    'node',
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
      'access corporate contact form',
    ]);
    $this->drupalLogin($testuser);

    // Prepare a node as redirect destination.
    $node_type = NodeType::create(['type' => 'page']);
    $node_type->save();

    $node = Node::create([
      'title' => 'Destination',
      'type' => 'page',
      'path' => ['alias' => '/destination'],
      'status' => TRUE,
      'uid' => 0,
    ]);
    $node->save();
  }

  /**
   * Tests for corporate forms behaviour.
   */
  public function testCorporateForm(): void {
    /** @var \Behat\Mink\WebAssert $assert */
    $assert = $this->assertSession();
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    // Prepare a corporate contact form.
    $settings = [];
    $contact_form_id = 'oe_contact_form';
    $contact_form = ContactForm::create(['id' => $contact_form_id]);
    $contact_form->setReply('this is a autoreply');
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'allow_canonical_url', TRUE);
    $settings['topic_label'] = 'Topic label';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topic_label', $settings['topic_label']);
    $settings['email_subject'] = 'Email Subject';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'email_subject', $settings['email_subject']);
    $settings['header'] = 'this is a test header';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'header', $settings['header']);
    $settings['privacy_text'] = 'this is a test privacy policy';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'privacy_policy', $settings['privacy_text']);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'includes_fields_in_auto_reply', TRUE);
    $settings['optional_selected'] = ['oe_country_residence' => 'oe_country_residence'];
    $contact_form->setThirdPartySetting('oe_contact_forms', 'optional_fields', $settings['optional_selected']);
    $settings['topics'] = [
      [
        'topic_name' => 'Topic name',
        'topic_email_address' => 'topic@emailaddress.com',
      ],
    ];
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topics', $settings['topics']);
    $contact_form->save();

    // Access canonical url.
    $this->drupalGet('contact/' . $contact_form_id);

    // Assert corporate fields and optional fields handling.
    $this->assertMessageForm($settings);

    // Submit the form.
    $this->assertMessageFormSubmission($settings);

    // Assert that instead of the user being redirected to the homepage,
    // they are redirected to the same, contact form page.
    $this->assertUrl('/contact/' . $contact_form_id);

    // Set redirect to an existing path, other then the current one.
    $contact_form->setRedirectPath('/destination');
    $contact_form->save();

    // Submit the form.
    $page->fillField('subject[0][value]', 'Test subject');
    $page->fillField('message[0][value]', 'Test message');
    $page->selectFieldOption('oe_topic', 'Topic name');
    $page->findField('privacy_policy')->click();
    $page->findButton('Send message')->press();

    // Assert that the user is being redirected to the path set.
    $this->assertUrl('/destination');
  }

  /**
   * Tests for corporate forms block behaviour.
   */
  public function testCorporateBlockForm(): void {
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    // Prepare a corporate contact form.
    $settings = [];
    $contact_form_id = 'oe_contact_form';
    $contact_form = ContactForm::create(['id' => $contact_form_id]);
    $contact_form->setReply('this is a autoreply');
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'allow_canonical_url', FALSE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'expose_as_block', TRUE);
    $settings['topic_label'] = 'Topic label';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topic_label', $settings['topic_label']);
    $settings['email_subject'] = 'Email Subject';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'email_subject', $settings['email_subject']);
    $settings['header'] = 'this is a test header';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'header', $settings['header']);
    $settings['privacy_text'] = 'this is a test privacy policy';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'privacy_policy', $settings['privacy_text']);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'includes_fields_in_auto_reply', TRUE);
    $settings['optional_selected'] = ['oe_country_residence' => 'oe_country_residence'];
    $contact_form->setThirdPartySetting('oe_contact_forms', 'optional_fields', $settings['optional_selected']);
    $settings['topics'] = [
      [
        'topic_name' => 'Topic name',
        'topic_email_address' => 'topic@emailaddress.com',
      ],
    ];
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topics', $settings['topics']);
    $contact_form->save();

    // Place the block on a page.
    $node = Node::create([
      'title' => 'Block Page',
      'type' => 'page',
      'path' => ['alias' => '/block-page'],
      'status' => TRUE,
      'uid' => 0,
    ]);
    $node->save();
    $block = $this->placeBlock('oe_contact_forms_corporate_block:' . $contact_form->uuid(), [
      'visibility' => [
        'request_path' => [
          'pages' => '/block-page',
        ],
      ],
    ]);
    $this->drupalGet('/block-page');

    // Assert corporate fields and optional fields handling.
    $this->assertMessageForm($settings);

    // Submit the form.
    $this->assertMessageFormSubmission($settings);

    // Assert that instead of the user being redirected to the homepage,
    // they are redirected to the same page.
    $this->assertUrl('/block-page');

    // Set redirect to an existing path, other then the current one.
    $contact_form->setRedirectPath('/destination');
    $contact_form->save();

    // Submit the form.
    $page->fillField('subject[0][value]', 'Test subject');
    $page->fillField('message[0][value]', 'Test message');
    $page->selectFieldOption('oe_topic', 'Topic name');
    $page->findField('privacy_policy')->click();
    $page->findButton('Send message')->press();

    // Assert that the user is being redirected to the path set.
    $this->assertUrl('/destination');
  }

  /**
   * Tests default form is not changed.
   */
  public function testDefaultFormNotChanged(): void {
    /** @var \Behat\Mink\WebAssert $assert */
    $assert = $this->assertSession();
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

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
    $page->fillField('subject[0][value]', 'Test subject');
    $page->fillField('message[0][value]', 'Test message');
    $page->findButton('Send message')->press();

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

    // Assert that non-corporate forms are being redirected to the homepage
    // (which in the case of the test setup is the user page).
    $this->assertUrl('/user/' . $this->loggedInUser->id());
  }

  /**
   * Assert the fields are as defined in corporate form settings.
   *
   * @param array $settings
   *   The corporate form settings.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ElementTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  protected function assertMessageForm(array &$settings = []) {
    /** @var \Behat\Mink\WebAssert $assert */
    $assert = $this->assertSession();
    // Assert corporate fields.
    $assert->fieldExists('oe_country_residence[0][target_id]');
    $assert->fieldNotExists('oe_telephone[0][value]');
    $assert->fieldExists('oe_topic');
    $assert->fieldExists('privacy_policy');
    // Assert header printed.
    $assert->pageTextContains($settings['header']);
    // Assert privacy text.
    $assert->pageTextContains($settings['privacy_text']);
    // Assert topic label.
    $assert->elementTextContains('css', 'label[for="edit-oe-topic"]', $settings['topic_label']);
    // Assert elements order.
    $elements = $this->xpath('//form');
    $this->assertCount(1, $elements);
    // TODO: is there a better method for this ?
    $html = $elements['0']->getOuterHtml();
    $i = [];
    $i['header'] = Unicode::strpos($html, $settings['header']);
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
  }

  /**
   * Assert corporate form behaviour.
   *
   * @param array $settings
   *   The corporate form settings.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ElementTextException
   */
  protected function assertMessageFormSubmission(array &$settings = []) {
    /** @var \Behat\Mink\WebAssert $assert */
    $assert = $this->assertSession();
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();
    $page->fillField('subject[0][value]', 'Test subject');
    $page->fillField('message[0][value]', 'Test message');
    $page->selectFieldOption('oe_topic', 'Topic name');
    $page->findField('privacy_policy')->click();
    $page->findButton('Send message')->press();

    // Assert confirmation message.
    $assert->elementTextContains('css', '.messages--status', $settings['privacy_text']);
    $assert->elementTextContains('css', '.messages--status', $settings['topics']['0']['topic_name']);

    // Load captured emails to check.
    $captured_emails = $this->drupalGetMails();
    $this->assertTrue(count($captured_emails) === 2);

    // Assert email subject.
    $this->assertTrue($captured_emails[0]['subject'] === $settings['email_subject']);
    // Assert email recipients.
    $this->assertTrue($captured_emails[0]['to'] === $settings['topics']['0']['topic_email_address']);
    // Make sure we have an autoreply.
    $this->assertTrue($captured_emails[1]['id'] === 'contact_page_autoreply');
    // Assert fields in autoreply.
    $this->assertTrue(strpos($captured_emails[1]['body'], 'Test subject') !== FALSE);
    $this->assertTrue(strpos($captured_emails[1]['body'], 'Test message') !== FALSE);
    $this->assertTrue(strpos($captured_emails[1]['body'], $settings['topics']['0']['topic_name']) !== FALSE);
  }

}
