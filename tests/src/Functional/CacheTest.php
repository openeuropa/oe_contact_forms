<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\Functional;

use Drupal\contact\Entity\ContactForm;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the corporate contact form rendering under cache.
 */
class CacheTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path',
    'user',
    'system',
    'dynamic_page_cache',
    'page_cache',
    'oe_contact_forms',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Allow anonymous users to use corporate contact forms.
    $this->grantPermissions(Role::load(RoleInterface::ANONYMOUS_ID), [
      'access site-wide contact form',
      'access corporate contact form',
    ]);
  }

  /**
   * Tests the corporate contact form rendering under cache.
   */
  public function testCacheInvalidation() {
    /** @var \Behat\Mink\WebAssert $assert */
    $assert = $this->assertSession();

    // Prepare a corporate contact form.
    $contact_form_id = 'oe_contact_form';
    $contact_form = ContactForm::create(['id' => $contact_form_id]);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'allow_canonical_url', TRUE);
    $topic_label = t('Topic label');
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topic_label', $topic_label);
    $header = t('this is a test header');
    $contact_form->setThirdPartySetting('oe_contact_forms', 'header', $header);
    $privacy_url = 'http://example.net';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'privacy_policy', $privacy_url);
    $optional_selected = [];
    $contact_form->setThirdPartySetting('oe_contact_forms', 'optional_fields', $optional_selected);
    $topics = [
      ['topic_name' => t('Topic name'), 'topic_email_address' => 'topic@emailaddress.com'],
    ];
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topics', $topics);
    $contact_form->save();

    // Access canonical url.
    $this->drupalGet('contact/' . $contact_form_id);

    // Assert corporate fields and optional fields handling.
    $assert->pageTextContains($topic_label);
    $assert->pageTextContains($header);
    $assert->elementAttributeContains('xpath', "//div[contains(@class, 'form-item-privacy-policy')]//a", 'href', $privacy_url);
    $assert->fieldNotExists('oe_country_residence[0][target_id]');
    $assert->fieldNotExists('oe_telephone[0][value]');

    foreach ($topics as $topic) {
      $assert->elementExists('named', ['option', $topic['topic_name']]);
    }

    // Change settings.
    $topic_label = t('Topic label changed');
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topic_label', $topic_label);
    $header = t('this is a changed test header');
    $contact_form->setThirdPartySetting('oe_contact_forms', 'header', $header);
    $privacy_url = 'http://changed.example.net';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'privacy_policy', $privacy_url);
    $optional_selected = [
      'oe_country_residence' => 'oe_country_residence',
      'oe_telephone' => 'oe_telephone',
    ];
    $contact_form->setThirdPartySetting('oe_contact_forms', 'optional_fields', $optional_selected);
    $topics = [
      ['topic_name' => t('Changed name'), 'topic_email_address' => 'changed@emailaddress.com'],
      ['topic_name' => t('Another topic'), 'topic_email_address' => 'another@emailaddress.com'],
    ];
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topics', $topics);
    $contact_form->save();

    // Access canonical url.
    $this->drupalGet('contact/' . $contact_form_id);

    // Assert rendering has changed.
    $assert->pageTextContains($topic_label);
    $assert->pageTextContains($header);
    $assert->elementAttributeContains('xpath', "//div[contains(@class, 'form-item-privacy-policy')]//a", 'href', $privacy_url);
    $assert->fieldExists('oe_country_residence[0][target_id]');
    $assert->fieldExists('oe_telephone[0][value]');

    foreach ($topics as $topic) {
      $assert->elementExists('named', ['option', $topic['topic_name']]);
    }
  }

}
