<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\FunctionalJavascript;

use Drupal\contact\Entity\ContactForm;
use Drupal\contact\Entity\Message;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;

/**
 * Tests the corporate contact forms.
 */
class CorporateContactFormTest extends WebDriverTestBase {

  use SparqlConnectionTrait;

  /**
   * Corporate fields to test against.
   *
   * @var array
   */
  protected $fields = [
    'corporate_fields[topic_label]' => 'Topic label',
    'corporate_fields[email_subject]' => 'Email subject',
    'corporate_fields[header]' => 'Header text',
    'corporate_fields[privacy_policy]' => 'http://example.net',
    'corporate_fields[includes_fields_in_auto_reply]' => TRUE,
    'corporate_fields[allow_canonical_url]' => TRUE,
    // For expose_as_block default is true so we test false.
    'corporate_fields[expose_as_block]' => FALSE,
    'corporate_fields[optional_fields][oe_country_residence]' => TRUE,
    'corporate_fields[optional_fields][oe_preferred_language]' => TRUE,
    'corporate_fields[optional_fields][oe_alternative_language]' => TRUE,
    'corporate_fields[optional_fields][oe_telephone]' => TRUE,
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'user',
    'path',
    'node',
    'oe_contact_forms',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create and login test user with permission to create contact forms.
    /** @var \Drupal\user\UserInterface $test_user */
    $test_user = $this->drupalCreateUser([
      'administer contact forms',
      'view published skos concept entities',
    ]);
    $this->drupalLogin($test_user);
  }

  /**
   * Test export of a corporate contact form.
   */
  public function testCorporateContactFormExport(): void {
    $this->drupalLogout();

    /** @var \Behat\Mink\WebAssert $assert */
    $assert = $this->assertSession();
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    // Prepare a corporate contact form.
    $contact_form_id = 'oe_contact_form';
    $contact_form = ContactForm::create(['id' => $contact_form_id]);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->save();

    // Create a sample message.
    $message = Message::create([
      'id' => 1,
      'contact_form' => $contact_form->id(),
      'name' => 'example',
      'mail' => 'admin@example.com',
      'created' => '1487321550',
      'ip_address' => '127.0.0.1',
      'subject' => 'Test subject',
      'message' => 'Test message',
    ]);
    $message->save();

    $account = $this->drupalCreateUser([
      'access administration pages',
      'access site-wide contact form',
      'administer contact forms',
      'export contact form messages',
    ]);
    $this->drupalLogin($account);

    // Check the form is correct.
    $this->drupalGet('admin/structure/contact/manage/export', ['query' => ['contact_form' => $contact_form_id]]);
    $assert->elementNotExists('css', '#edit-columns-ip-address');
    $field_ids = [
      'edit-columns-id',
      'edit-columns-langcode',
      'edit-columns-contact-form',
      'edit-columns-name',
      'edit-columns-mail',
      'edit-columns-subject',
      'edit-columns-message',
      'edit-columns-copy',
      'edit-columns-recipient',
      'edit-columns-created',
      'edit-columns-uid',
      'edit-columns-oe-country-residence',
      'edit-columns-oe-preferred-language',
      'edit-columns-oe-alternative-language',
      'edit-columns-oe-telephone',
      'edit-columns-oe-telephone',
    ];

    foreach ($field_ids as $field_id) {
      $assert->checkboxChecked($field_id);
    }

    $page->pressButton('Export');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Assert CSV output with selected columns.
    /** @var \Drupal\file\FileInterface[] $files */
    $files = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties(['filename' => 'contact-storage-export.csv']);
    $file = reset($files);
    $absolute_path = \Drupal::service('file_system')->realpath($file->getFileUri());
    $actual = file_get_contents($absolute_path);

    $headers = '"Message ID",Language,"Form ID","The sender\'s name","The sender\'s email",Subject,Message,Copy,"Recipient ID",Created,"User ID","Country of residence","Preferred contact language","Alternative contact language",Phone,Topic';
    $values = '1,English,,example,admin@example.com,"Test subject","Test message",,,"Fri, 02/17/2017 - 19:52",0,,,,,';
    $expected = $headers . PHP_EOL . $values;
    $this->assertEquals($expected, $actual);
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

    // Assert contact_storage_disabled_form_message is visible.
    $disabled_message = $page->findField('contact_storage_disabled_form_message');
    $this->assertTrue($disabled_message->isVisible());

    // Assert corporate fields are not present before is_corporate_form click.
    $is_corporate_form = $page->findField('is_corporate_form');
    $this->assertNotEmpty($is_corporate_form);
    $this->assertFieldsVisible(FALSE);

    // Ajax call to load corporate fields.
    $is_corporate_form->click();
    $assert->assertWaitOnAjaxRequest();

    // Assert contact_storage_disabled_form_message was hidden.
    $this->assertFalse($disabled_message->isVisible());

    // Assert fields are now visible.
    $this->assertFieldsVisible(TRUE);

    // Assert alternative contact language and options are active only when
    // preferred contact language field is active.
    $preferred_language_element = $page->findField('corporate_fields[optional_fields][oe_preferred_language]');
    $alternative_language_element = $page->findField('corporate_fields[optional_fields][oe_alternative_language]');
    $override_languages_element = $page->find('css', '[data-drupal-selector="edit-corporate-fields-override-languages"]');
    $this->assertFalse($preferred_language_element->isChecked());
    $this->assertFalse($alternative_language_element->isChecked());
    $this->assertEquals('disabled', $alternative_language_element->getAttribute('disabled'));
    $this->assertFalse($override_languages_element->isVisible());

    $preferred_language_element->click();
    $alternative_language_element = $page->findField('corporate_fields[optional_fields][oe_alternative_language]');
    $this->assertNotNull($alternative_language_element);
    $this->assertEmpty($alternative_language_element->getAttribute('disabled'));
    $this->assertTrue($override_languages_element->isVisible());
    $override_languages_element->click();
    $this->assertLanguageOptions('oe_preferred_language_options');
    $this->assertAlternativeContactOptionsVisible(FALSE);

    $alternative_language_element->click();
    $this->assertTrue($preferred_language_element->isChecked());
    $this->assertTrue($alternative_language_element->isChecked());
    $this->assertAlternativeContactOptionsVisible(TRUE);
    $this->assertLanguageOptions('oe_alternative_language_options');

    $preferred_language_element->click();
    $this->assertFalse($alternative_language_element->isChecked());
    $this->assertEquals('disabled', $alternative_language_element->getAttribute('disabled'));
    $this->assertAlternativeContactOptionsVisible(FALSE);
    $this->assertFalse($override_languages_element->isVisible());

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

    // Assert contact_storage_disabled_form_message is hidden.
    $disabled_message = $page->findField('contact_storage_disabled_form_message');
    $this->assertFalse($disabled_message->isVisible());

    // Make sure the saved values are the ones expected.
    $this->checkCorporateFieldsOnPage(1, TRUE);
    $this->checkCorporateFieldsInStorage(1, TRUE);

    // Add more topic values, retest ajax.
    $this->assertTopicAjax(1);

    // Go through the edit process.
    $page->pressButton('Save');
    $assert->pageTextContains('Contact form Test form has been updated.');

    // Assert the values are saved.
    $this->drupalGet('admin/structure/contact/manage/oe_corporate_form');

    // Make sure the saved values are the ones expected.
    $this->checkCorporateFieldsOnPage(2, TRUE);
    $this->checkCorporateFieldsInStorage(2, TRUE);

    // Assert internal links for privacy policy.
    $field_name = 'corporate_fields[privacy_policy]';
    $element = $page->findField($field_name);
    $value = '<front>';
    $element->setValue($value);
    $page->pressButton('Save');
    $assert->pageTextContains('Contact form Test form has been updated.');

    // Make sure the saved value is the one expected.
    $this->drupalGet('admin/structure/contact/manage/oe_corporate_form');
    $element = $page->findField($field_name);
    $this->assertNotEmpty($element);
    $this->assertEquals($value, $element->getValue());

    // Test with entity reference.
    $alias = '/privacy-page';
    $node = Node::create([
      'title' => 'Privacy page',
      'type' => 'page',
      'path' => ['alias' => $alias],
      'status' => TRUE,
      'uid' => 0,
    ]);
    $node->save();
    $value = 'Privacy page (' . $node->id() . ')';
    $element->setValue($value);
    $page->pressButton('Save');
    $assert->pageTextContains('Contact form Test form has been updated.');

    $this->drupalGet('admin/structure/contact/manage/oe_corporate_form');
    $element = $page->findField($field_name);
    $this->assertNotEmpty($element);
    $this->assertEquals($value, $element->getValue());

    // Test with node alias.
    $element->setValue($alias);

    // Uncheck alternative language to assert its state after form loading.
    $alternative_language_element = $page->findField('corporate_fields[optional_fields][oe_alternative_language]');
    $alternative_language_element->click();
    $this->assertFalse($alternative_language_element->isChecked());

    $page->pressButton('Save');
    $assert->pageTextContains('Contact form Test form has been updated.');

    $this->drupalGet('admin/structure/contact/manage/oe_corporate_form');
    $element = $page->findField($field_name);
    $this->assertNotEmpty($element);
    $this->assertEquals($alias, $element->getValue());

    // Assert alternative language element is active if preferred is checked.
    $preferred_language_element = $page->findField('corporate_fields[optional_fields][oe_preferred_language]');
    $alternative_language_element = $page->findField('corporate_fields[optional_fields][oe_alternative_language]');
    $this->assertTrue($preferred_language_element->isChecked());
    $this->assertFalse($alternative_language_element->isChecked());
    $this->assertEmpty($alternative_language_element->getAttribute('disabled'));
    $override_languages_element = $page->find('css', '[data-drupal-selector="edit-corporate-fields-override-languages"]');
    $this->assertTrue($override_languages_element->isVisible());
    $override_languages_element->click();
    $this->assertAlternativeContactOptionsVisible(FALSE);
  }

  /**
   * Test corporate values are not set on default form.
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
    $this->assertFieldsVisible(FALSE);

    // Ajax call to load corporate fields.
    $is_corporate_form->click();
    $assert->assertWaitOnAjaxRequest();

    // Check that fields are now created.
    $this->assertFieldsVisible(TRUE);

    // Click again to remove them.
    $is_corporate_form->click();
    $assert->assertWaitOnAjaxRequest();

    // Check that fields are now removed.
    $this->assertFieldsVisible(FALSE);

    // Add contact required values.
    $this->fillCoreContactFields();

    $page->pressButton('Save');
    $assert->pageTextContains('Contact form Test form has been added.');

    // Assert the values are saved.
    $this->drupalGet('admin/structure/contact/manage/oe_corporate_form');
    $this->assertFieldsVisible(FALSE);
    $this->checkCorporateFieldsNotInStorage();
  }

  /**
   * Test transition from corporate to default form.
   */
  public function testCorporateDefaultTransition(): void {
    /** @var \Behat\Mink\WebAssert $assert */
    $assert = $this->assertSession();
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    $this->drupalGet('admin/structure/contact/add');

    // Ajax call to load corporate fields.
    $is_corporate_form = $page->findField('is_corporate_form');
    $this->assertNotEmpty($is_corporate_form);
    $is_corporate_form->click();
    $assert->assertWaitOnAjaxRequest();

    // Assert fields are now visible.
    $this->assertFieldsVisible(TRUE);

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

    // Make sure the saved values are the ones expected.
    $this->checkCorporateFieldsOnPage();
    $this->checkCorporateFieldsInStorage();

    // Now we transition to default form.
    $is_corporate_form->click();
    $assert->assertWaitOnAjaxRequest();

    // Assert fields are now removed.
    $this->assertFieldsVisible(FALSE);

    $page->pressButton('Save');
    $assert->pageTextContains('Contact form Test form has been updated.');

    // Assert no corporate values are saved.
    $this->drupalGet('admin/structure/contact/manage/oe_corporate_form');
    $this->assertFieldsVisible(FALSE);
    $this->checkCorporateFieldsNotInStorage();
  }

  /**
   * Helper to assert fields are missing.
   *
   * @var boolean $presence
   */
  protected function assertFieldsVisible($presence): void {
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
   *
   * @param int $delta
   *   The group index.
   */
  protected function assertTopicAjax($delta = 0): void {
    /** @var \Behat\Mink\WebAssert $assert */
    $assert = $this->assertSession();
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    $topic_name_key = "corporate_fields[topics_fieldset][group][$delta][topic_name]";
    $topic_email_key = "corporate_fields[topics_fieldset][group][$delta][topic_email_address]";

    $page->fillField($topic_name_key, 'topic-' . $delta);
    $page->fillField($topic_email_key, $delta . '-topic@email.com');

    // Test add topic group.
    $add_topic = $page->find('css', 'input[value="Add topic"]');
    $this->assertNotEmpty($add_topic);
    $add_topic->click();
    $assert->assertWaitOnAjaxRequest();

    $delta++;
    $topic_name_key = "corporate_fields[topics_fieldset][group][$delta][topic_name]";
    $topic_email_key = "corporate_fields[topics_fieldset][group][$delta][topic_email_address]";

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
    $page->fillField($topic_name_key, 'topic-' . $delta);
    $page->fillField($topic_email_key, $delta . '-topic@email.com');
    $add_topic->click();
    $assert->assertWaitOnAjaxRequest();

    $delta++;
    $topic_name_key = "corporate_fields[topics_fieldset][group][$delta][topic_name]";
    $topic_email_key = "corporate_fields[topics_fieldset][group][$delta][topic_email_address]";

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

    // Assert previous two are still there.
    $delta--;
    $topic_name_key = "corporate_fields[topics_fieldset][group][$delta][topic_name]";
    $topic_email_key = "corporate_fields[topics_fieldset][group][$delta][topic_email_address]";

    $element = $page->findField($topic_name_key);
    $this->assertNotEmpty($element);
    $element = $page->findField($topic_email_key);
    $this->assertNotEmpty($element);
  }

  /**
   * Asserts language options fields.
   *
   * @param string $field_name
   *   Field name.
   */
  protected function assertLanguageOptions(string $field_name): void {
    /** @var \Behat\Mink\WebAssert $assert */
    $assert = $this->assertSession();
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();
    $field_name_selector = str_replace('_', '-', $field_name);

    // Assert there are 25 input elements with 24 languages in specific order
    // and last empty element.
    $input_elements = $page->findAll('css', '[data-drupal-selector="edit-corporate-fields-override-languages-' . $field_name_selector . '"] .form-autocomplete');
    $this->assertEquals(25, count($input_elements));
    $expected_values = [
      0 => 'Bulgarian (http://publications.europa.eu/resource/authority/language/BUL)',
      1 => 'Spanish (http://publications.europa.eu/resource/authority/language/SPA)',
      2 => 'Czech (http://publications.europa.eu/resource/authority/language/CES)',
      3 => 'Danish (http://publications.europa.eu/resource/authority/language/DAN)',
      21 => 'Slovenian (http://publications.europa.eu/resource/authority/language/SLV)',
      22 => 'Finnish (http://publications.europa.eu/resource/authority/language/FIN)',
      23 => 'Swedish (http://publications.europa.eu/resource/authority/language/SWE)',
    ];
    foreach ($expected_values as $key => $value) {
      $this->assertEquals($value, $input_elements[$key]->getValue());
    }
    for ($i = 4; $i < 20; $i++) {
      $this->assertNotEmpty($input_elements[$key]->getValue());
    }
    $this->assertEmpty($input_elements[24]->getValue());

    // Replace last and first elements and add duplicates to ensure order of
    // saved elements and removing of duplicates.
    $page->fillField("corporate_fields[override_languages][$field_name][0][target]", 'Swedish (http://publications.europa.eu/resource/authority/language/SWE)');
    $page->fillField("corporate_fields[override_languages][$field_name][23][target]", 'Bulgarian (http://publications.europa.eu/resource/authority/language/BUL)');
    $page->fillField("corporate_fields[override_languages][$field_name][24][target]", 'Bulgarian (http://publications.europa.eu/resource/authority/language/BUL)');

    // Assert "Add more item" button.
    $add_another_element = $page->find('css', '[name="corporate_fields_override_languages_' . $field_name . '_add_more"]');
    $add_another_element->click();
    $assert->assertWaitOnAjaxRequest();
    $input_elements = $page->findAll('css', '[data-drupal-selector="edit-corporate-fields-override-languages-' . $field_name_selector . '"] .form-autocomplete');
    $this->assertEquals(26, count($input_elements));
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
   *
   * @param int $max_delta
   *   The max topic group index.
   * @param bool $language_options_filled
   *   Whether language options are filled or not.
   */
  protected function checkCorporateFieldsOnPage($max_delta = 1, $language_options_filled = FALSE): void {
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    // Dynamically add topic fields.
    for ($i = 0; $i <= $max_delta; $i++) {
      $this->fields["corporate_fields[topics_fieldset][group][$i][topic_name]"] = 'topic-' . $i;
      $this->fields["corporate_fields[topics_fieldset][group][$i][topic_email_address]"] = $i . '-topic@email.com';
    }

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

    $expected_values = [];
    $expected_values[1] = 'Spanish (http://publications.europa.eu/resource/authority/language/SPA)';
    $expected_values[22] = 'Finnish (http://publications.europa.eu/resource/authority/language/FIN)';
    if ($language_options_filled) {
      $expected_values[0] = 'Swedish (http://publications.europa.eu/resource/authority/language/SWE)';
      $expected_values[23] = 'Bulgarian (http://publications.europa.eu/resource/authority/language/BUL)';
    }
    else {
      $expected_values[0] = 'Bulgarian (http://publications.europa.eu/resource/authority/language/BUL)';
      $expected_values[23] = 'Swedish (http://publications.europa.eu/resource/authority/language/SWE)';
    }
    $fields = [
      'oe-preferred-language-options',
      'oe-alternative-language-options',
    ];
    foreach ($fields as $field_name) {
      $input_elements = $page->findAll('css', '[data-drupal-selector="edit-corporate-fields-override-languages-' . $field_name . '"] .form-autocomplete');
      $this->assertEquals(25, count($input_elements));
      foreach ($expected_values as $key => $value) {
        $this->assertEquals($value, $input_elements[$key]->getValue());
      }
    }
  }

  /**
   * Check that the values saved in storage are the ones expected.
   *
   * @param int $max_delta
   *   The max topic group index.
   * @param bool $language_options_filled
   *   Whether language options are filled or not.
   */
  protected function checkCorporateFieldsInStorage($max_delta = 1, bool $language_options_filled = FALSE): void {
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
      'privacy_policy' => 'http://example.net',
      'includes_fields_in_auto_reply' => TRUE,
      'allow_canonical_url' => TRUE,
      'expose_as_block' => FALSE,
      'optional_fields' => [
        'oe_country_residence' => 'oe_country_residence',
        'oe_preferred_language' => 'oe_preferred_language',
        'oe_alternative_language' => 'oe_alternative_language',
        'oe_telephone' => 'oe_telephone',
      ],
      'topics' => [],
    ];

    // Dynamically add topic fields.
    for ($i = 0; $i <= $max_delta; $i++) {
      $expected_values['topics'][] = [
        'topic_name' => 'topic-' . $i,
        'topic_email_address' => $i . '-topic@email.com',
      ];
    }

    foreach ($expected_values as $key => $expected) {
      $value = $contact_form->getThirdPartySetting('oe_contact_forms', $key);
      $this->assertEquals($expected, $value);
    }

    // Check saved language options.
    $value = $contact_form->getThirdPartySetting('oe_contact_forms', 'override_languages');
    if ($language_options_filled) {
      $expected = [
        0 => 'http://publications.europa.eu/resource/authority/language/SWE',
        1 => 'http://publications.europa.eu/resource/authority/language/SPA',
        2 => 'http://publications.europa.eu/resource/authority/language/CES',
        22 => 'http://publications.europa.eu/resource/authority/language/FIN',
        23 => 'http://publications.europa.eu/resource/authority/language/BUL',
      ];
      $fields = [
        'oe_preferred_language_options',
        'oe_alternative_language_options',
      ];
      foreach ($fields as $field_name) {
        foreach ($expected as $key => $expected_value) {
          $this->assertEquals($expected_value, $value[$field_name][$key]);
        }
        $this->assertEquals(24, count($value[$field_name]));
      }
    }
    else {
      $this->assertEquals([
        'oe_preferred_language_options' => [],
        'oe_alternative_language_options' => [],
      ], $value);
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
      'is_corporate_form' => FALSE,
      'topic_label' => NULL,
      'email_subject' => NULL,
      'header' => NULL,
      'privacy_policy' => NULL,
      'includes_fields_in_auto_reply' => NULL,
      'allow_canonical_url' => NULL,
      'expose_as_block' => NULL,
      'optional_fields' => NULL,
      'topics' => NULL,
      'override_languages' => NULL,
    ];

    foreach ($expected_values as $key => $expected) {
      $value = $contact_form->getThirdPartySetting('oe_contact_forms', $key);
      $this->assertEquals($expected, $value);
    }
  }

  /**
   * Asserts visibility of "Alternative contact language options" field.
   *
   * @param bool $visible
   *   Whether field is visible or not.
   */
  protected function assertAlternativeContactOptionsVisible(bool $visible): void {
    $element = $this->getSession()->getPage()->find('css', '[data-drupal-selector="edit-corporate-fields-override-languages-oe-alternative-language-options"]');
    if ($visible) {
      $this->assertStringNotContainsString('display: none', $element->getAttribute('style'));
    }
    else {
      $this->assertStringContainsString('display: none', $element->getAttribute('style'));
    }
  }

}
