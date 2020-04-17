<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\Kernel;

use Drupal\Tests\rdf_entity\Kernel\RdfKernelTestBase;
use Drupal\contact\Entity\ContactForm;
use Drupal\contact\Entity\Message;

/**
 * Tests contact form with new fields.
 */
class BaseFieldTest extends RdfKernelTestBase {

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
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('contact_message');
    $this->installConfig(['field', 'system']);
    module_load_include('install', 'oe_contact_forms');
    oe_contact_forms_install();
  }

  /**
   * Tests successful corporate forms message.
   */
  public function testCorporateFormBaseFields(): void {
    $contact_form_id = 'oe_contact_form';
    $contact_form = ContactForm::create(['id' => $contact_form_id]);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topics', ['test']);
    $contact_form->save();

    $data = [
      'id' => 1,
      'contact_form' => $contact_form->id(),
      'name' => 'example',
      'mail' => 'admin@example.com',
      'created' => '1487321550',
      'ip_address' => '127.0.0.1',
      'subject' => 'Test subject',
      'message' => 'Test message',
    ];
    $data['oe_country_residence'] = 'http://publications.europa.eu/resource/authority/country/BEL';
    $data['oe_telephone'] = '0123456';
    $data['oe_topic'] = 0;

    $message = Message::create($data);
    $message->save();

    $entity_type_manager = \Drupal::entityTypeManager()->getStorage('contact_message');
    $entity_type_manager->resetCache();
    /** @var \Drupal\contact\Entity\Message $message */
    $message = $entity_type_manager->load($message->id());

    $this->assertEquals($data['oe_country_residence'], $message->get('oe_country_residence')->getValue()['0']['target_id']);
    $this->assertEquals($data['oe_telephone'], $message->get('oe_telephone')->getValue()['0']['value']);
    $this->assertEquals($data['oe_topic'], $message->get('oe_topic')->getValue()['0']['value']);
  }

}
