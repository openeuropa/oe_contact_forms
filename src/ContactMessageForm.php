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
    /** @var \Drupal\contact\MessageInterface $contact_message */
    $message = $this->entity;
    /** @var \Drupal\contact\ContactFormInterface $contact_form */
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

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): void {
    /** @var \Drupal\contact\MessageInterface $contact_message */
    $message = $this->entity;
    /** @var \Drupal\contact\ContactFormInterface $contact_form */
    $contact_form = $message->getContactForm();

    // If the form is configured to include all the fields in the auto-reply,
    // set the values after the auto-reply body,
    // so they get included in the email as well.
    $reply = $contact_form->getReply();
    $includes_fields_in_auto_reply = (boolean) $contact_form->getThirdPartySetting('oe_contact_forms', 'includes_fields_in_auto_reply', FALSE);

    if (!empty($reply) && $includes_fields_in_auto_reply === TRUE) {
      $mail_view = $this->entityTypeManager->getViewBuilder('contact_message')->view($message, 'mail');
      $reply .= "\n" . \Drupal::service('renderer')->render($mail_view);
      $contact_form->setReply($reply);
    }

    // Set on the ContactMessage the configured subject.
    $email_subject = $contact_form->getThirdPartySetting('oe_contact_forms', 'email_subject', FALSE);

    if (!empty($email_subject)) {
      $subject = $message->getSubject();
      $message->setSubject($email_subject . ' - ' . $subject);
    }

    // Set the email recipient(s) based on the selected topic.
    $recipients = $contact_form->getRecipients();
    $topics = $contact_form->getThirdPartySetting('oe_contact_forms', 'topics', []);
    $selected_topics = $form_state->getValue('oe_topic')['0']['value'];

    if (isset($topics[$selected_topics])) {
      $topic_recipients = explode(',', $topics[$selected_topics]['topic_email_address']);
      $recipients = array_merge($recipients, $topic_recipients);
      $contact_form->setRecipients($recipients);
    }

    // Send the emails.
    parent::save($form, $form_state);

    // Apart from the confirmation message also include the following.
    // Privacy notice.
    $privacy_policy = $contact_form->getThirdPartySetting('oe_contact_forms', 'privacy_policy', '');

    if (!empty($privacy_policy)) {
      $this->messenger()->addMessage($privacy_policy);
    }
    // The values of the submitted fields.
    $full_view = $this->entityTypeManager->getViewBuilder('contact_message')->view($message, 'full');
    $this->messenger()->addMessage($full_view);
  }

}
