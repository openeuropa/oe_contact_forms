<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\Kernel;

use Drupal\Tests\rdf_entity\Kernel\RdfKernelTestBase;

/**
 * Base test class for contact form kernel tests.
 */
class ContactFormTestBase extends RdfKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'options',
    'telephone',
    'contact',
    'contact_storage',
    'rdf_skos',
    'oe_contact_forms',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('contact_message');
  }

}
