<?php

declare(strict_types = 1);

namespace Drupal\oe_contact_forms\Commands\sql;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;
use Drush\Drupal\Commands\sql\SanitizePluginInterface;
use Symfony\Component\Console\Input\InputInterface;

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
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * SanitizeContactFormFieldsCommands constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, Connection $connection) {
    parent::__construct();
    $this->entityTypeManager = $entityTypeManager;
    $this->connection = $connection;
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
        $this->connection->update('contact_message')
          ->expression('name', 'CONCAT(:name_dummy_string, id)', [
            ':name_dummy_string' => 'User',
          ])
          ->expression('mail', 'CONCAT(:mail_dummy_string, id, :maildomain_dummy_string)', [
            ':mail_dummy_string' => 'user+',
            ':maildomain_dummy_string' => '@example.com',
          ])
          ->expression('subject', 'CONCAT(:subject_dummy_string, id)', [
            ':subject_dummy_string' => 'subject-by-',
          ])
          ->expression('message', 'CONCAT(:message_dummy_string, id)', [
            ':message_dummy_string' => 'message-by-',
          ])
          ->expression('oe_country_residence', 'CONCAT(:residence_dummy_string, id)', [
            ':residence_dummy_string' => 'residence-in-',
          ])
          ->expression('oe_telephone', 'CONCAT(:telephone_dummy_string, id)', [
            ':telephone_dummy_string' => '+000-',
          ])
          ->expression('oe_topic', 'CONCAT(:topic_dummy_string, id)', [
            ':topic_dummy_string' => 'topic-',
          ])
          ->fields(['ip_address' => '127.0.0.1'])
          ->condition('contact_form', $contact_form->id())
          ->execute();

        // Make sure that we don't have sensitive data of contact messages
        // in the cache.
        $this->entityTypeManager->getStorage('contact_message')->resetCache();

        $topics = $contact_form->getThirdPartySetting('oe_contact_forms', 'topics', []);
        foreach ($topics as $key => $topic) {
          if (!empty($topic['topic_email_address'])) {
            $topics[$key]['topic_email_address'] = 'topic+' . $key . '@example.com';
          }
        }
        $contact_form->setThirdPartySetting('oe_contact_forms', 'topics', $topics);

        $recipients = $contact_form->getRecipients();
        foreach ($recipients as $key => $recipient) {
          if (!empty($recipient)) {
            $recipients[$key] = 'recipient+' . $key . '@example.com';
          }
        }
        $contact_form->setRecipients($recipients);
        $contact_form->save();
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
