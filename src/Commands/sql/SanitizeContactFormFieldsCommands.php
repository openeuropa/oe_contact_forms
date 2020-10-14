<?php

declare(strict_types = 1);

namespace Drupal\oe_contact_forms\Commands\sql;

use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Input\InputInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Sanitizes the contact forms related data.
 */
class SanitizeContactFormFieldsCommands extends DrushCommands {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * SanitizeContactFormFieldsCommands constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger service.
   */
  public function __construct(
    Connection $database,
    ModuleHandler $moduleHandler,
    LoggerChannelFactoryInterface $loggerFactory) {
    $this->database = $database;
    $this->moduleHandler = $moduleHandler;
    $this->logger = $loggerFactory->get('oe_contact_forms');
  }

  /**
   * Sanitize the contact data from the DB.
   *
   * @hook post-command sql-sanitize
   *
   * @inheritdoc
   */
  public function sanitize() {
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
      ->execute();

    $this->logger->notice('Contact messages data sanitized');
  }

  /**
   * Sets the output message.
   *
   * @hook on-event sql-sanitize-confirms
   *
   * @inheritdoc
   */
  public function messages(array &$messages, InputInterface $input) {
    $messages[] = dt('Sanitize contact form data.');
  }

}
