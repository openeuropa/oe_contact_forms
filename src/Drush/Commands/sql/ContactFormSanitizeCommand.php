<?php

declare(strict_types=1);

namespace Drupal\oe_contact_forms\Drush\Commands\sql;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Drush\Drupal\Commands\sql\SanitizePluginInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sanitizes the contact forms related data.
 *
 * @phpstan-ignore class.implementsDeprecatedInterface
 */
final class ContactFormSanitizeCommand extends DrushCommands implements SanitizePluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

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
   * Returns a new instance.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @todo when dropping drush 12 support, replace 'sql:sanitize' with
   *   Drush\Commands\sql\sanitize\SanitizeCommands::SANITIZE.
   */
  #[CLI\Hook(type: HookManager::POST_COMMAND_HOOK, target: 'sql:sanitize')]
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
          ->expression('oe_first_name', 'CONCAT(:first_name_dummy_string, id)', [
            ':first_name_dummy_string' => 'first-name-',
          ])
          ->expression('oe_last_name', 'CONCAT(:last_name_dummy_string, id)', [
            ':last_name_dummy_string' => 'last-name-',
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
   * {@inheritdoc}
   *
   * @todo when dropping drush 12 support, replace 'sql:sanitize' with
   *   Drush\Commands\sql\sanitize\SanitizeCommands::CONFIRMS.
   */
  #[CLI\Hook(type: HookManager::ON_EVENT, target: 'sql-sanitize-confirms')]
  public function messages(&$messages, InputInterface $input) {
    return $messages[] = dt('Sanitize contact form data.');
  }

}
