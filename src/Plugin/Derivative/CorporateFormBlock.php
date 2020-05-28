<?php

declare(strict_types = 1);

namespace Drupal\oe_contact_forms\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver class for corporate contact form blocks.
 */
class CorporateFormBlock extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * LinkListBlock constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    /** @var \Drupal\contact\ContactFormInterface[] $contact_forms */
    $contact_forms = $this->entityTypeManager->getStorage('contact_form')->loadMultiple();
    foreach ($contact_forms as $key => $contact_form) {
      $expose_as_block = $contact_form->getThirdPartySetting('oe_contact_forms', 'expose_as_block', FALSE);
      $is_corporate_form = $contact_form->getThirdPartySetting('oe_contact_forms', 'is_corporate_form', FALSE);

      // Only expose corporate forms configured as block.
      if ($expose_as_block && $is_corporate_form) {
        $this->derivatives[$contact_form->uuid()] = $base_plugin_definition;
        $this->derivatives[$contact_form->uuid()]['admin_label'] = $contact_form->label();
        $this->derivatives[$contact_form->uuid()]['config_dependencies']['config'] = [$contact_form->getConfigDependencyName()];
      }

    }

    return $this->derivatives;
  }

}
