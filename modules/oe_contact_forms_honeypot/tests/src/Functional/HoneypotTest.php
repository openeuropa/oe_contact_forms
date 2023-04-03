<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms_honeypot\Functional;

use Drupal\contact\Entity\ContactForm;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;

/**
 * Contact form honeypot test.
 */
class HoneypotTest extends BrowserTestBase {

  use SparqlConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_contact_forms_honeypot',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $honeypot_config = \Drupal::configFactory()
      ->getEditable('honeypot.settings');
    $honeypot_config->set('element_name', 'url');

    // Disable time_limit protection.
    $honeypot_config->set('time_limit', 0);
    $honeypot_config->save();

    $test_user = $this->drupalCreateUser([
      'access site-wide contact form',
      'access corporate contact form',
    ]);
    $this->drupalLogin($test_user);
  }

  /**
   * Tests the honeypot integration.
   */
  public function testCreateContactForm() {
    $assert_session = $this->assertSession();

    $contact_form_id = 'oe_contact_form';
    $contact_form = ContactForm::create(['id' => $contact_form_id]);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'allow_canonical_url', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topics', [
      [
        'topic_name' => 'Topic name',
        'topic_email_address' => 'topic@emailaddress.com',
      ],
    ]);
    $privacy_url = 'http://example.net';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'privacy_policy', $privacy_url);
    $contact_form->save();
    $this->drupalGet('contact/oe_contact_form');

    // Assert honeypot is enabled and working for the contact form.
    $assert_session->fieldExists('url');
  }

}
