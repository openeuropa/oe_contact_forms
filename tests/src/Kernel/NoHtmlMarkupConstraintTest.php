<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_contact_forms\Kernel;

use Drupal\Component\Utility\Html;
use Drupal\contact\Entity\ContactForm;
use Drupal\contact\Entity\Message;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Form\FormState;

/**
 * Tests HTML markup validation on contact message plain-text fields.
 */
class NoHtmlMarkupConstraintTest extends ContactFormTestBase {

  /**
   * Contact form used by the tests.
   *
   * @var \Drupal\contact\Entity\ContactForm
   */
  protected ContactForm $contactForm;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Contact's form validation expects flood-control configuration.
    \Drupal::configFactory()
      ->getEditable('contact.settings')
      ->set('flood.limit', 5)
      ->set('flood.interval', 3600)
      ->save();

    $this->contactForm = ContactForm::create([
      'id' => 'markup_validation',
      'label' => 'Markup validation',
    ]);
    $this->contactForm->save();
  }

  /**
   * Tests that all relevant base fields receive the constraint.
   */
  public function testBaseFieldConstraintAttachment(): void {
    $message = Message::create([
      'contact_form' => $this->contactForm->id(),
    ]);

    $field_names = [
      'name',
      'subject',
      'message',
      'oe_first_name',
      'oe_last_name',
      'oe_telephone',
    ];

    foreach ($field_names as $field_name) {
      $constraints = $message
        ->getFieldDefinition($field_name)
        ->getConstraints();

      $this->assertArrayHasKey(
        'OeContactFormsNoHtmlMarkup',
        $constraints,
        sprintf('%s has NoHtmlMarkup validation.', $field_name),
      );
    }
  }

  /**
   * Tests markup detection without rejecting legitimate plain text.
   */
  public function testPlainTextValidation(): void {
    $valid_values = [
      'John Doe',
      "O'Connor",
      'Smith & Wesson',
      'Amount < 100 EUR',
      '2 > 1',
      'x<y',
      'a <tag',
      'AT&amp;amp;T',
    ];

    foreach ($valid_values as $value) {
      $message = Message::create([
        'contact_form' => $this->contactForm->id(),
        'message' => $value,
      ]);

      $this->assertCount(
        0,
        $message->get('message')->validate(),
        sprintf('Legitimate plain text accepted: %s', $value),
      );
    }

    $encoded_markup_10 = '<script>alert(1)</script>';

    for ($depth = 0; $depth < 10; $depth++) {
      $encoded_markup_10 = Html::escape($encoded_markup_10);
    }

    $encoded_markup_11 = Html::escape($encoded_markup_10);

    $invalid_values = [
      '<script>alert(1)</script>',
      '<img src=x onerror=alert(1)>',
      '&lt;script&gt;alert(1)&lt;/script&gt;',
      '&amp;lt;script&amp;gt;alert(1)&amp;lt;/script&amp;gt;',
      '<strong>John</strong>',
      '<svg/onload=alert(1)>',
      '<!-- comment -->',
      '<!DOCTYPE html>',
      $encoded_markup_10,
      $encoded_markup_11,
    ];

    foreach ($invalid_values as $value) {
      $message = Message::create([
        'contact_form' => $this->contactForm->id(),
        'message' => $value,
      ]);

      $violations = $message->get('message')->validate();

      $this->assertCount(
        1,
        $violations,
        sprintf('HTML markup rejected: %s', $value),
      );
      $this->assertSame(
        'Message must not contain HTML markup.',
        (string) $violations[0]->getMessage(),
      );
    }
  }

  /**
   * Tests the constraint on bundle-configured plain-text fields.
   */
  public function testBundleFieldConstraint(): void {
    FieldStorageConfig::create([
      'field_name' => 'field_reference',
      'entity_type' => 'contact_message',
      'type' => 'string',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_reference',
      'entity_type' => 'contact_message',
      'bundle' => $this->contactForm->id(),
      'label' => 'Reference',
    ])->save();

    $message = Message::create([
      'contact_form' => $this->contactForm->id(),
      'field_reference' => '<img src=x onerror=alert(1)>',
    ]);

    $constraints = $message
      ->getFieldDefinition('field_reference')
      ->getConstraints();

    $this->assertArrayHasKey('OeContactFormsNoHtmlMarkup', $constraints);
    $this->assertCount(
      1,
      $message->get('field_reference')->validate(),
    );

    $message->set('field_reference', 'A<B');

    $this->assertCount(
      0,
      $message->get('field_reference')->validate(),
    );
  }

  /**
   * Tests validation through the actual standard and corporate forms.
   */
  public function testFormSubmissionValidation(): void {
    $standard_form = ContactForm::create([
      'id' => 'standard_markup_validation',
      'label' => 'Standard markup validation',
      'recipients' => ['recipient@example.com'],
    ]);
    $standard_form->save();

    $form_state = $this->submitMessageForm(
      $standard_form,
      'default',
      [
        'name' => 'John Doe',
        'mail' => 'john@example.com',
        'subject' => [
          ['value' => 'Normal subject'],
        ],
        'message' => [
          ['value' => '<script>alert(1)</script>'],
        ],
      ],
    );

    $this->assertFormHasError(
      $form_state,
      'Message must not contain HTML markup.',
    );

    // The sender name is built manually by Drupal core rather than through a
    // field widget. Verify its constraint is still mapped to FormState.
    $form_state = $this->submitMessageForm(
      $standard_form,
      'default',
      [
        'name' => '<img src=x onerror=alert(1)>',
        'mail' => 'john@example.com',
        'subject' => [
          ['value' => 'Normal subject'],
        ],
        'message' => [
          ['value' => 'Normal message'],
        ],
      ],
    );

    $this->assertFormHasErrorByName(
      $form_state,
      'name',
      "The sender's name must not contain HTML markup.",
    );

    $corporate_form = ContactForm::create([
      'id' => 'corporate_markup_validation',
      'label' => 'Corporate markup validation',
    ]);
    $corporate_form->setThirdPartySetting(
      'oe_contact_forms',
      'is_corporate_form',
      TRUE,
    );
    $corporate_form->setThirdPartySetting(
      'oe_contact_forms',
      'alternative_name',
      TRUE,
    );
    $corporate_form->setThirdPartySetting(
      'oe_contact_forms',
      'privacy_policy',
      'https://example.com/privacy',
    );
    $corporate_form->setThirdPartySetting(
      'oe_contact_forms',
      'email_subject',
      'Corporate contact',
    );
    $corporate_form->setThirdPartySetting(
      'oe_contact_forms',
      'topics',
      [
        [
          'topic_name' => 'General',
          'topic_email_address' => 'general@example.com',
        ],
      ],
    );
    $corporate_form->setThirdPartySetting(
      'oe_contact_forms',
      'optional_fields',
      [
        'oe_telephone' => 'oe_telephone',
      ],
    );
    $corporate_form->save();

    $message = Message::create([
      'contact_form' => $corporate_form->id(),
    ]);
    $built_form = \Drupal::service('entity.form_builder')->getForm(
      $message,
      'corporate_default',
    );

    $this->assertNotContains(
      'oe_contact_forms_name_no_html_markup_validate',
      $built_form['name']['#element_validate'] ?? [],
      'Hidden corporate sender name must not receive the manual validator.',
    );

    $form_state = $this->submitMessageForm(
      $corporate_form,
      'corporate_default',
      [
        'mail' => 'john@example.com',
        'subject' => [
          ['value' => 'Normal subject'],
        ],
        'message' => [
          ['value' => 'Normal message'],
        ],
        'oe_first_name' => [
          ['value' => 'John'],
        ],
        'oe_last_name' => [
          ['value' => '<img src=x onerror=alert(1)>'],
        ],
        'oe_telephone' => [
          ['value' => '+32 123 456'],
        ],
        'oe_topic' => 'General',
        'privacy_policy' => 1,
      ],
    );

    $this->assertFormHasError(
      $form_state,
      'Last name must not contain HTML markup.',
    );

    // Corporate forms can use Drupal core's normal sender-name field when
    // alternative first/last name fields are disabled.
    $corporate_form->setThirdPartySetting(
      'oe_contact_forms',
      'alternative_name',
      FALSE,
    );
    $corporate_form->save();

    $message = Message::create([
      'contact_form' => $corporate_form->id(),
    ]);
    $built_form = \Drupal::service('entity.form_builder')->getForm(
      $message,
      'corporate_default',
    );

    $this->assertContains(
      'oe_contact_forms_name_no_html_markup_validate',
      $built_form['name']['#element_validate'] ?? [],
      'Editable corporate sender name must receive the manual validator.',
    );

    $form_state = $this->submitMessageForm(
      $corporate_form,
      'corporate_default',
      [
        'name' => '<script>alert(1)</script>',
        'mail' => 'john@example.com',
        'subject' => [
          ['value' => 'Normal subject'],
        ],
        'message' => [
          ['value' => 'Normal message'],
        ],
        'oe_telephone' => [
          ['value' => '+32 123 456'],
        ],
        'oe_topic' => 'General',
        'privacy_policy' => 1,
      ],
    );

    $this->assertFormHasErrorByName(
      $form_state,
      'name',
      "The sender's name must not contain HTML markup.",
    );
  }

  /**
   * Submits a contact message entity form programmatically.
   *
   * @param \Drupal\contact\Entity\ContactForm $contact_form
   *   Contact form configuration.
   * @param string $operation
   *   Entity form operation.
   * @param array $values
   *   Raw form values.
   *
   * @return \Drupal\Core\Form\FormState
   *   Processed form state.
   */
  private function submitMessageForm(ContactForm $contact_form, string $operation, array $values): FormState {
    $message = Message::create([
      'contact_form' => $contact_form->id(),
    ]);

    $form_object = \Drupal::entityTypeManager()
      ->getFormObject('contact_message', $operation);
    $form_object->setEntity($message);

    $form_state = new FormState();
    $form_state->setValues($values);

    \Drupal::formBuilder()->submitForm(
      $form_object,
      $form_state,
    );

    return $form_state;
  }

  /**
   * Asserts a validation error on a specific form element.
   *
   * @param \Drupal\Core\Form\FormState $form_state
   *   Processed form state.
   * @param string $name
   *   Form error key.
   * @param string $expected
   *   Expected validation message.
   */
  private function assertFormHasErrorByName(FormState $form_state, string $name, string $expected): void {
    $errors = $form_state->getErrors();

    $this->assertArrayHasKey(
      $name,
      $errors,
      sprintf(
        'Expected validation error on "%s". Actual keys: %s',
        $name,
        implode(', ', array_keys($errors)),
      ),
    );

    $actual = Html::decodeEntities((string) $errors[$name]);

    $this->assertSame(
      $expected,
      $actual,
      sprintf(
        'Unexpected validation error on "%s".',
        $name,
      ),
    );
  }

  /**
   * Asserts that a form contains a specific validation error.
   *
   * @param \Drupal\Core\Form\FormState $form_state
   *   Processed form state.
   * @param string $expected
   *   Expected validation message.
   */
  private function assertFormHasError(FormState $form_state, string $expected): void {
    $errors = array_map(
      static fn ($error): string => (string) $error,
      $form_state->getErrors(),
    );

    $this->assertContains(
      $expected,
      $errors,
      sprintf(
        'Expected validation error "%s". Actual errors: %s',
        $expected,
        implode(' | ', $errors),
      ),
    );
  }

  /**
   * Tests contextual escaping of hostile values already present in storage.
   */
  public function testStoredMarkupIsEscapedOnOutput(): void {
    $payload = 'OEL4951-MARKER <script>alert(1)</script>';

    $message = Message::create([
      'contact_form' => $this->contactForm->id(),
      'name' => 'John Doe',
      'mail' => 'john@example.com',
      'subject' => 'Security output test',
      'message' => $payload,
    ]);

    // Deliberately bypass validation to simulate pre-existing or externally
    // persisted hostile data. Output rendering remains a secondary defence.
    $message->save();

    $storage = \Drupal::entityTypeManager()
      ->getStorage('contact_message');

    $storage->resetCache([$message->id()]);

    $stored_message = $storage->load($message->id());

    $this->assertNotNull($stored_message);

    $view_builder = \Drupal::entityTypeManager()
      ->getViewBuilder('contact_message');

    $renderer = \Drupal::service('renderer');

    foreach (['full', 'mail'] as $view_mode) {
      $build = $view_builder->view(
        $stored_message,
        $view_mode,
      );

      $output = (string) $renderer->renderRoot($build);

      // Positive assertions prove that the hostile field is really rendered,
      // rather than simply omitted from the display.
      $this->assertStringContainsString(
        'OEL4951-MARKER',
        $output,
        sprintf('Message is rendered in %s view mode.', $view_mode),
      );

      $this->assertStringContainsString(
        '&lt;script&gt;alert(1)&lt;/script&gt;',
        $output,
        sprintf('Markup is escaped in %s view mode.', $view_mode),
      );

      $this->assertStringNotContainsString(
        '<script>alert(1)</script>',
        $output,
        sprintf('Raw markup is absent from %s view mode.', $view_mode),
      );
    }
  }

}
