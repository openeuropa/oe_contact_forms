<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for Kernel tests that test translation functionality.
 */
class ContactFormsKernelTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'system',
    'field',
    'options',
    'views',
    'telephone',
    'contact',
    'contact_storage',
    'rdf_entity',
    'rdf_skos',
    'oe_contact_forms',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('contact_message');
    $this->installConfig(['field', 'system']);
    module_load_include('install', 'oe_contact_forms');
    oe_contact_forms_install();
  }

}
