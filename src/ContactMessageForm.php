<?php

declare(strict_types = 1);

namespace Drupal\oe_contact_forms;

use Drupal\contact\MessageForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for contact message forms.
 *
 * @internal
 */
class ContactMessageForm extends MessageForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\contact\Entity\Message $message */
    $message = $this->entity;
    /** @var \Drupal\contact\Entity\ContactForm $contact_form */
    $contact_form = $message->getContactForm();

    $header = $contact_form->getThirdPartySetting('oe_contact_forms', 'header', FALSE);

    if (!empty($header)) {
      $form['header'] = [
        '#markup' => $header,
      ];
    }

    // Checkbox to accept privacy policy configured in the ContactForm.
    $form['privacy_policy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Privacy policy'),
      '#description' => $contact_form->getThirdPartySetting('oe_contact_forms', 'privacy_policy', ''),
      '#required' => TRUE,
    ];

    // Hide the optional fields the form manager has not configured to be used.
    $optional_selected = $contact_form->getThirdPartySetting('oe_contact_forms', 'optional_fields', ['oe_country_residence', 'oe_telephone']);

    if (!in_array('oe_country_residence', $optional_selected)) {
      $form['oe_country_residence']['#access'] = FALSE;
    }

    if (!in_array('oe_telephone', $optional_selected)) {
      $form['oe_telephone']['#access'] = FALSE;
    }

    // Alter the Topics label with the value configured by the form manager.
    $form['oe_topic']['widget']['#title'] = $contact_form->getThirdPartySetting('oe_contact_forms', 'topic_label', 'Topics');

    return $form;
  }

}
