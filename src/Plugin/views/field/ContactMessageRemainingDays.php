<?php

declare(strict_types=1);

namespace Drupal\oe_contact_forms\Plugin\views\field;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to display the remaining days of a message.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("contact_message_remaining_days")
 */
class ContactMessageRemainingDays extends FieldPluginBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\contact\Entity\Message $entity */
    $entity = $this->getEntity($values);
    $created_date = $entity->get('created')->value;
    $auto_delete_days = $this->configFactory->get('contact.settings')->get('auto_delete');
    if ($auto_delete_days === NULL) {
      return [];
    }
    else {
      $remaining_days = $auto_delete_days - floor((time() - $created_date) / (60 * 60 * 24));
    }

    $remaining_days = (int) $remaining_days < 0 ? 0 : (int) $remaining_days;
    $remaining_days = $remaining_days === 1 ? $remaining_days . ' day' : $remaining_days . ' days';
    $build = [];
    $build['remaining_days'] = [
      '#markup' => $remaining_days,
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->addAdditionalFields();
  }

}
