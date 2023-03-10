<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\node\Entity\Node;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Test Corporate MessageForm behaviour.
 */
class MessageFormTest extends WebDriverTestBase {

  use SparqlConnectionTrait;

  use AssertMailTrait {
    getMails as drupalGetMails;
  }

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'path',
    'node',
    'dynamic_page_cache',
    'page_cache',
    'contact',
    'contact_storage',
    'oe_contact_forms',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Allow anonymous users to use corporate contact forms.
    $this->grantPermissions(Role::load(RoleInterface::ANONYMOUS_ID), [
      'access site-wide contact form',
      'access corporate contact form',
    ]);
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
    $contact_form_id = 'oe_contact_form';
    $contact_form = ContactForm::create(['id' => $contact_form_id]);
    $contact_form->setReply('this is a autoreply');
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'allow_canonical_url', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'alternative_name', TRUE);
    $topic_label = 'Topic label';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topic_label', $topic_label);
    $email_subject = 'Email Subject';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'email_subject', $email_subject);
    $header = 'this is a test header';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'header', $header);
    $privacy_url = 'http://example.net';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'privacy_policy', $privacy_url);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'includes_fields_in_auto_reply', TRUE);
    $optional_selected = ['oe_country_residence' => 'oe_country_residence'];
    $contact_form->setThirdPartySetting('oe_contact_forms', 'optional_fields', $optional_selected);
    $topics = [
      [
        'topic_name' => 'Topic name',
        'topic_email_address' => 'topic@emailaddress.com',
      ],
    ];
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topics', $topics);
    $contact_form->save();

    // Access canonical url.
    $this->drupalGet('contact/' . $contact_form_id);

    // Assert first name and last name fields are visible.
    $assert->fieldExists('First name');
    $assert->fieldExists('Last name');
    // Assert corporate fields and optional fields handling.
    $assert->fieldExists('oe_country_residence');
    // Assert country options are ordered by label and deprecated ones
    // have been filtered out.
    $options = $this->getOptions('oe_country_residence');
    $actual_countries = array_slice($options, 0, 5);
    $expected_countries = [
      '_none' => '- None -',
      'http://publications.europa.eu/resource/authority/country/AFG' => 'Afghanistan',
      'http://publications.europa.eu/resource/authority/country/ALA' => 'Åland Islands',
      'http://publications.europa.eu/resource/authority/country/ALB' => 'Albania',
      'http://publications.europa.eu/resource/authority/country/DZA' => 'Algeria',
    ];
    $this->assertEquals($expected_countries, $actual_countries);
    $assert->fieldNotExists('oe_preferred_language');
    $assert->fieldNotExists('oe_alternative_language');
    $assert->fieldNotExists('oe_telephone[0][value]');
    $assert->fieldExists('oe_topic');
    $assert->fieldExists('privacy_policy');
    // Assert header printed.
    $assert->pageTextContains($header);
    // Assert privacy text.
    $assert->pageTextContains('I have read and agree with the personal data protection terms');
    // Assert privacy text link.
    $assert->elementAttributeContains('xpath', "//div[contains(@class, 'form-item-privacy-policy')]//a", 'target', "_blank");
    // Assert topic label.
    $assert->elementTextContains('css', 'label[for="edit-oe-topic"]', $topic_label);
    // Assert elements order.
    $elements = $this->xpath('//form');
    $this->assertCount(1, $elements);
    // @todo is there a better method for this ?
    $html = $elements['0']->getOuterHtml();
    $i = [];
    $i['name'] = mb_strpos($html, 'edit-name');
    $i['email'] = mb_strpos($html, 'edit-mail');
    $i['subject'] = mb_strpos($html, 'edit-subject-0-value');
    $i['message'] = mb_strpos($html, 'edit-message-0-value');
    $i['topic'] = mb_strpos($html, 'edit-oe-topic');
    $i['country'] = mb_strpos($html, 'edit-oe-country-residence');
    $i['privacy'] = mb_strpos($html, 'edit-privacy-policy');

    $this->assertTrue($i['name'] < $i['email']);
    $this->assertTrue($i['email'] < $i['subject']);
    $this->assertTrue($i['subject'] < $i['message']);
    $this->assertTrue($i['message'] < $i['topic']);
    $this->assertTrue($i['topic'] < $i['country']);
    $this->assertTrue($i['country'] < $i['privacy']);

    // Change settings.
    $topic_label = 'Topic label changed';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topic_label', $topic_label);
    $header = 'this is a changed test header';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'header', $header);
    $privacy_url = 'http://changed.example.net';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'privacy_policy', $privacy_url);
    $optional_selected = [
      'oe_country_residence' => 'oe_country_residence',
      'oe_preferred_language' => 'oe_preferred_language',
      'oe_alternative_language' => 'oe_alternative_language',
      'oe_telephone' => 'oe_telephone',
    ];
    $contact_form->setThirdPartySetting('oe_contact_forms', 'optional_fields', $optional_selected);
    $topics = [
      [
        'topic_name' => 'Changed name',
        'topic_email_address' => 'changed@emailaddress.com',
      ],
      [
        'topic_name' => 'Another topic',
        'topic_email_address' => 'another@emailaddress.com',
      ],
    ];
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topics', $topics);
    $contact_form->save();

    // Access canonical url.
    $this->drupalGet('contact/' . $contact_form_id);

    // Assert rendering has changed.
    $assert->pageTextContains($topic_label);
    $assert->pageTextContains($header);
    $assert->elementAttributeContains('xpath', "//div[contains(@class, 'form-item-privacy-policy')]//a", 'href', $privacy_url);
    $assert->fieldExists('oe_country_residence');
    $assert->fieldExists('Preferred contact language');
    $assert->fieldExists('Alternative contact language');
    $assert->fieldExists('oe_telephone[0][value]');

    // Assert contact language fields contains 24 EU languages by default.
    $expected_languages = [
      '- None -' => '- None -',
      'http://publications.europa.eu/resource/authority/language/BUL' => 'Bulgarian',
      'http://publications.europa.eu/resource/authority/language/SPA' => 'Spanish',
      'http://publications.europa.eu/resource/authority/language/CES' => 'Czech',
      'http://publications.europa.eu/resource/authority/language/DAN' => 'Danish',
    ];
    $options = $this->getOptions('Preferred contact language');
    $this->assertEquals(25, count($options));
    $actual_preferred_language = array_slice($options, 0, 5);
    $this->assertEquals($expected_languages, $actual_preferred_language);
    $options = $this->getOptions('Alternative contact language');
    $this->assertEquals(25, count($options));
    $actual_alternative_language = array_slice($options, 0, 5);
    $this->assertEquals($expected_languages, $actual_alternative_language);

    foreach ($topics as $topic) {
      $assert->elementExists('named', ['option', $topic['topic_name']]);
    }

    // Submit the form.
    $page->fillField('First name', 'thanos');
    $page->fillField('Last name', 'tester');
    $page->fillField('mail', 'tester@example.com');
    $page->fillField('subject[0][value]', 'Test subject');
    $page->fillField('message[0][value]', 'Test message');
    $page->selectFieldOption('oe_topic', $topics['0']['topic_name']);
    $page->findField('privacy_policy')->click();
    $page->findButton('Send message')->press();

    // Assert preferred contact language field is required.
    $assert->elementNotExists('css', 'div[aria-label="Status message"]');
    $page->selectFieldOption('Preferred contact language', 'http://publications.europa.eu/resource/authority/language/CES');
    $page->findButton('Send message')->press();

    // Assert confirmation message.
    $assert->elementTextContains('css', 'div[aria-label="Status message"]', $topics['0']['topic_name']);

    // Load captured emails to check.
    $captured_emails = $this->drupalGetMails();
    $this->assertTrue(count($captured_emails) === 2);

    // Assert email subject.
    $this->assertTrue($captured_emails[0]['subject'] === $email_subject);
    // Assert email recipients.
    $this->assertTrue($captured_emails[0]['to'] === $topics['0']['topic_email_address']);
    // Make sure we have an autoreply.
    $this->assertTrue($captured_emails[1]['id'] === 'contact_page_autoreply');
    $this->assertTestEmailBodies($captured_emails);

    // Assert that instead of the user being redirected to the homepage,
    // they are redirected to the same, contact form page.
    $assert->addressEquals('/contact/' . $contact_form_id);

    // Set redirect to an existing path, other then the current one.
    $node = Node::create([
      'title' => 'Destination',
      'type' => 'page',
      'path' => ['alias' => '/destination'],
      'status' => TRUE,
      'uid' => 0,
    ]);
    $node->save();
    $contact_form->setRedirectPath('/destination');
    $contact_form->save();

    // Submit the form.
    $page->fillField('First name', 'thanos');
    $page->fillField('Last name', 'tester');
    $page->fillField('mail', 'tester@example.com');
    $page->fillField('subject[0][value]', 'Test subject');
    $page->fillField('message[0][value]', 'Test message');
    $page->selectFieldOption('oe_topic', $topics['0']['topic_name']);
    $page->findField('privacy_policy')->click();
    $page->selectFieldOption('Preferred contact language', 'http://publications.europa.eu/resource/authority/language/CES');
    $page->findButton('Send message')->press();

    // Assert that the user is being redirected to the path set.
    $assert->addressEquals('/destination');

    // Assert internal value for privacy policy.
    $alias = '/privacy-page';
    $node = Node::create([
      'title' => 'Privacy page',
      'type' => 'page',
      'path' => ['alias' => $alias],
      'status' => TRUE,
      'uid' => 0,
    ]);
    $node->save();
    $contact_form->setThirdPartySetting('oe_contact_forms', 'privacy_policy', 'internal:' . $alias);
    $override_languages = [
      'oe_preferred_language_options' => [
        'http://publications.europa.eu/resource/authority/language/CES',
        'http://publications.europa.eu/resource/authority/language/DAN',
        'http://publications.europa.eu/resource/authority/language/ZUL',
      ],
      'oe_alternative_language_options' => [
        'http://publications.europa.eu/resource/authority/language/FRA',
        'http://publications.europa.eu/resource/authority/language/QUE',
      ],
    ];
    $contact_form->setThirdPartySetting('oe_contact_forms', 'override_languages', $override_languages);
    $contact_form->save();
    $this->drupalGet('contact/' . $contact_form_id);
    $assert->elementAttributeContains('xpath', "//div[contains(@class, 'form-item-privacy-policy')]//a", 'href', $alias);
    $expected_languages = [
      '- None -' => '- None -',
      'http://publications.europa.eu/resource/authority/language/CES' => 'Czech',
      'http://publications.europa.eu/resource/authority/language/DAN' => 'Danish',
      'http://publications.europa.eu/resource/authority/language/ZUL' => 'Zulu',
    ];
    $options = $this->getOptions('Preferred contact language');
    $this->assertEquals($expected_languages, $options);
    $expected_languages = [
      '- None -' => '- None -',
      'http://publications.europa.eu/resource/authority/language/FRA' => 'French',
      'http://publications.europa.eu/resource/authority/language/QUE' => 'Quechua',
    ];
    $options = $this->getOptions('Alternative contact language');
    $this->assertEquals($expected_languages, $options);
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
    $page->fillField('name', 'tester');
    $page->fillField('mail', 'tester@example.com');
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

    // Assert that non-corporate forms are being redirected to the homepage.
    $assert->addressEquals('/');
  }

  /**
   * Asserts the sent emails are correctly formatted.
   *
   * @param array $captured_emails
   *   The captured emails that were sent out: main one and auto-reply.
   *
   * @see self::testCorporateForm
   */
  protected function assertTestEmailBodies(array $captured_emails): void {
    // First email is the outgoing one.
    $expected = <<<EOF
thanos tester (not verified) (tester@example.com) sent a message using the contact
form at http://web:8080/build/contact/oe_contact_form.



      The sender's name
                thanos tester



      The sender's email
                tester@example.com



      Subject
                Test subject



      Message
                Test message



      First name
                thanos



      Last name
                tester



      Topic
                Changed name

EOF;

    $this->assertEquals(preg_replace('/\s+/', ' ', $expected), preg_replace('/\s+/', ' ', $captured_emails[0]['body']));

    // The second is the autoreply.
    $expected = <<<EOF
this is a autoreply


      The sender's name
                thanos tester



      The sender's email
                tester@example.com



      Subject
                Test subject



      Message
                Test message



      First name
                thanos



      Last name
                tester



      Topic
                Changed name

EOF;

    $this->assertEquals(preg_replace('/\s+/', ' ', $expected), preg_replace('/\s+/', ' ', $captured_emails[1]['body']));
  }

}
