<?php

declare(strict_types = 1);

namespace Drupal\oe_contact_forms\Plugin\Block;

use Drupal\contact\ContactFormInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block that renders a corporate form.
 *
 * @Block(
 *   id = "oe_contact_forms_corporate_block",
 *   admin_label = @Translation("Corporate form"),
 *   category = @Translation("Corporate forms"),
 *   deriver = "Drupal\oe_contact_forms\Plugin\Derivative\CorporateFormBlock",
 * )
 */
class CorporateFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The EntityFormBuilder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilder
   */
  protected $entityFormBuilder;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CorporateFormBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entityTypeManager, EntityFormBuilderInterface $entity_form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    /** @var \Drupal\contact\ContactFormInterface $contact_form */
    $contact_form = $this->getContactForm();

    if (!$contact_form) {
      return AccessResult::forbidden();
    }

    if (!$contact_form->status()) {
      // Deny access in case the form is disabled.
      return AccessResult::forbidden()->addCacheableDependency($contact_form);
    }

    return $this->entityTypeManager->getAccessControlHandler('contact_message')->createAccess($contact_form->id(), $account, [], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\contact\ContactFormInterface $contact_form */
    $contact_form = $this->getContactForm();
    if (!$contact_form) {
      return [];
    }

    /** @var \Drupal\contact\MessageInterface $contact_message */
    $message = $this->entityTypeManager->getStorage('contact_message')->create([
      'contact_form' => $contact_form->id(),
    ]);

    $form = $this->entityFormBuilder->getForm($message, 'corporate_default');
    $cache = new CacheableMetadata();
    $cache->addCacheableDependency($contact_form);
    $cache->applyTo($form);

    return $form;
  }

  /**
   * Returns the derived contact form.
   *
   * @return \Drupal\contact\ContactFormInterface|null
   *   The contact form entity.
   */
  protected function getContactForm(): ?ContactFormInterface {
    $uuid = $this->getDerivativeId();
    $results = $this->entityTypeManager->getStorage('contact_form')->loadByProperties(['uuid' => $uuid]);
    if (!$results) {
      // Normally, this should not happen but in case the entity has been
      // deleted.
      return NULL;
    }

    return reset($results);
  }

}
