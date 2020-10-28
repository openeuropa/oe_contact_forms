<?php

declare(strict_types = 1);

namespace Drupal\oe_contact_forms\Commands\sql;

use Drush\Commands\DrushCommands;
use Drush\Drupal\Commands\sql\SanitizePluginInterface;
use Consolidation\AnnotatedCommand\CommandData;
use Symfony\Component\Console\Input\InputInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Sanitizes the contact forms related data.
 */
class ContactFormSanitizeCommand extends DrushCommands implements SanitizePluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SanitizeContactFormFieldsCommands constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct();
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Sanitize the contact data from the DB.
   *
   * @hook post-command sql-sanitize
   *
   * @inheritdoc
   */
  public function sanitize($result, CommandData $commandData) {
    /** @var \Drupal\contact\ContactFormInterface[] $contact_forms */
    $contact_forms = $this->entityTypeManager->getStorage('contact_form')->loadMultiple();

    foreach ($contact_forms as $key => $contact_form) {
      $is_corporate_form = $contact_form->getThirdPartySetting('oe_contact_forms', 'is_corporate_form', FALSE);
      if ($is_corporate_form) {
        $messages = $this->entityTypeManager->getStorage('contact_message')->loadByProperties(['contact_form' => $contact_form->id()]);
        foreach ($messages as $message) {
          $message->set('name', 'User' . $message->id());
          $message->set('mail', 'user+' . $message->id() . '@example.com');
          $message->set('subject', 'subject-by-' . $message->id());
          $message->set('message', 'message-by-' . $message->id());
          $message->set('oe_country_residence', 'residence-in-' . $message->id());
          $message->set('oe_telephone', '+000-' . $message->id());
          $message->set('oe_topic', 'topic-' . $message->id());
          $message->set('ip_address', '127.0.0.1');
          $message->save();
        }
      }
    }

    $this->logger->success('Contact messages data sanitized.');
  }

  /**
   * Sets the output message.
   *
   * @hook on-event sql-sanitize-confirms
   *
   * @inheritdoc
   */
  public function messages(&$messages, InputInterface $input) {
    $messages[] = dt('Sanitize contact form data.');
  }

}
