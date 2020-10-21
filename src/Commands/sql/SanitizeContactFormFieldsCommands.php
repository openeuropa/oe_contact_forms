<?php

declare(strict_types = 1);

namespace Drupal\oe_contact_forms\Commands\sql;

use Drush\Commands\DrushCommands;
use Drush\Drupal\Commands\sql\SanitizePluginInterface;
use Consolidation\AnnotatedCommand\CommandData;
use Symfony\Component\Console\Input\InputInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Sanitizes the contact forms related data.
 */
class SanitizeContactFormFieldsCommands extends DrushCommands implements SanitizePluginInterface {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The entity cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $entityCache;

  /**
   * SanitizeContactFormFieldsCommands constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $entityCache
   *   The entity cache service.
   */
  public function __construct(Connection $database, LoggerChannelFactoryInterface $loggerFactory, EntityTypeManagerInterface $entityTypeManager, CacheBackendInterface $entityCache) {
    $this->database = $database;
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerFactory->get('oe_contact_forms');
    $this->entityCache = $entityCache;
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
        $this->database->update('contact_message')
          ->expression('name', "CONCAT('User', id)")
          ->expression('mail', "CONCAT('user+', id, '@example.com')")
          ->expression('subject', "CONCAT('subject-by-', id)")
          ->expression('message', "CONCAT('message-by-', id)")
          ->expression('oe_country_residence', "CONCAT('residence-in-', id)")
          ->expression('oe_telephone', "CONCAT('+000-', id)")
          ->expression('oe_topic', "CONCAT('topic-', id)")
          ->fields([
            'ip_address' => '127.0.0.1',
          ])
          ->condition('contact_form', $contact_form->id(), '=')
          ->execute();

        $select = $this->database->select('contact_message', 'cm');
        $select->fields('cm', ['id']);
        $select->condition('contact_form', $contact_form->id(), '=');
        $results = $select->execute()->fetchAll();

        $cids = [];

        foreach ($results as $result) {
          $cids[] = 'values:contact_message:' . $result->id;
        }

        $this->entityCache->invalidateMultiple($cids);
      }
    }

    $this->logger->notice('Contact messages data sanitized.');
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
