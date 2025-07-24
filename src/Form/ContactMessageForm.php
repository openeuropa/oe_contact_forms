<?php

declare(strict_types=1);

namespace Drupal\oe_contact_forms\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\contact\ContactFormInterface;
use Drupal\contact\MessageForm;
use Drupal\contact\MessageInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for contact message forms.
 *
 * @internal
 */
class ContactMessageForm extends MessageForm {

  /**
   * Constructs a MessageForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood control mechanism.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\contact\MailHandlerInterface $mail_handler
   *   The contact mail handler service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    protected $entity_repository,
    protected $flood,
    protected $language_manager,
    protected $mail_handler,
    protected $date_formatter,
    protected RendererInterface $renderer,
    protected $entity_type_bundle_info = NULL,
    protected $time = NULL,
  ) {
    parent::__construct($entity_repository, $flood, $language_manager, $mail_handler, $date_formatter, $entity_type_bundle_info, $time);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('flood'),
      $container->get('language_manager'),
      $container->get('contact.mail_handler'),
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

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
    $privacy_policy = $contact_form->getThirdPartySetting('oe_contact_forms', 'privacy_policy');
    $privacy_link = Link::fromTextAndUrl($this->t('personal data protection terms'), Url::fromUri($privacy_policy, [
      'absolute' => TRUE,
      'attributes' => ['target' => '_blank'],
    ]));
    $form['privacy_policy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I have read and agree with the @link', ['@link' => $privacy_link->toString()]),
      '#required' => TRUE,
    ];

    // Hide the optional fields the form manager has not configured to be used.
    $optional_selected = $contact_form->getThirdPartySetting('oe_contact_forms', 'optional_fields', []);

    $optional_fields = [
      'oe_country_residence',
      'oe_preferred_language',
      'oe_alternative_language',
      'oe_telephone',
    ];
    foreach ($optional_fields as $optional_field) {
      if (!in_array($optional_field, $optional_selected)) {
        $form[$optional_field]['#access'] = FALSE;
      }
    }

    // Alter the Topics label with the value configured by the form manager.
    $topic_label = $contact_form->getThirdPartySetting('oe_contact_forms', 'topic_label');

    if (!empty($topic_label)) {
      $form['oe_topic']['widget']['#title'] = $topic_label;
    }

    // Show/hide name fields based on setting.
    $alternative_name = $contact_form->getThirdPartySetting('oe_contact_forms', 'alternative_name');
    if ($alternative_name) {
      $form['name']['#access'] = FALSE;
    }
    else {
      $form['oe_first_name']['#access'] = FALSE;
      $form['oe_last_name']['#access'] = FALSE;
    }

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

    // Save the alternative name into name field based on the setting.
    $alternative_name = $contact_form->getThirdPartySetting('oe_contact_forms', 'alternative_name');
    if ($alternative_name) {
      $name = $message->get('oe_first_name')->value . ' ' . $message->get('oe_last_name')->value;
      $message->set('name', $name)->save();
    }

    $this->setReply($message, $contact_form);
    $this->setRecepients($contact_form, $form_state);

    // Send the emails.
    parent::save($form, $form_state);

    $this->addStatusMessage($message);
    $this->setRedirectUrl($contact_form, $form_state);
  }

  /**
   * Sets an auto-reply message to send to the message author.
   *
   * @param \Drupal\contact\MessageInterface $message
   *   Contact message instance.
   * @param \Drupal\contact\ContactFormInterface $contact_form
   *   Contact form instance.
   */
  protected function setReply(MessageInterface $message, ContactFormInterface $contact_form): void {
    // If the form is configured to include all the fields in the auto-reply,
    // set the values after the auto-reply body, so they get included in the
    // email as well.
    $reply = $contact_form->getReply();
    $includes_fields_in_messages = (boolean) $contact_form->getThirdPartySetting('oe_contact_forms', 'includes_fields_in_messages', FALSE);

    if (!empty($reply) && $includes_fields_in_messages === TRUE) {
      $mail_view = $this->entityTypeManager
        ->getViewBuilder('contact_message')
        ->view($message, 'mail');
      $reply .= "\n" . \Drupal::service('renderer')->renderInIsolation($mail_view);
      $contact_form->setReply($reply);
    }
  }

  /**
   * Sets list of recipient email addresses.
   *
   * @param \Drupal\contact\ContactFormInterface $contact_form
   *   Contact form instance.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  protected function setRecepients(ContactFormInterface $contact_form, FormStateInterface $form_state): void {
    // Set the email recipient(s) based on the selected topic.
    $recipients = $contact_form->getRecipients();
    $topics = $contact_form->getThirdPartySetting('oe_contact_forms', 'topics', []);
    $selected_topic = $form_state->getValue('oe_topic')['0']['value'];

    foreach ($topics as $topic) {
      if ($topic['topic_name'] === $selected_topic) {
        $topic_recipients = explode(',', $topic['topic_email_address']);
        $recipients = array_merge($recipients, $topic_recipients);
        $contact_form->setRecipients($recipients);
        break;
      }
    }
  }

  /**
   * Adds status message.
   *
   * @param \Drupal\contact\MessageInterface $message
   *   Contact message instance.
   */
  protected function addStatusMessage(MessageInterface $message): void {
    // Apart from the confirmation message also include the following.
    // The values of the submitted fields.
    $message = $this->entityTypeManager->getViewBuilder('contact_message')->view($message, 'full');
    $full_view = $this->renderer->renderInIsolation($message);
    $this->messenger()->addStatus($full_view);
  }

  /**
   * Sets redirect URL.
   *
   * @param \Drupal\contact\ContactFormInterface $contact_form
   *   Contact form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  protected function setRedirectUrl(ContactFormInterface $contact_form, FormStateInterface $form_state): void {
    // Redirect back to same page if redirect value is not set.
    if (!$contact_form->getRedirectPath()) {
      $form_state->setRedirectUrl(Url::fromRoute('<current>'));
    }
  }

}
