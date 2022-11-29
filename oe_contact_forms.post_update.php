<?php

/**
 * @file
 * Post update functions for OpenEuropa Contact Forms module.
 */

declare(strict_types = 1);

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Enable the corporate countries component.
 */
function oe_contact_forms_post_update_00001(): void {
  \Drupal::service('module_installer')->install(['oe_corporate_countries']);
  \Drupal::service('kernel')->invalidateContainer();
}

/**
 * Add "Preferred contact language", "Alternative contact language" base fields.
 */
function oe_contact_forms_post_update_00002(): void {
  // Enable new dependency.
  \Drupal::service('module_installer')->install(['multivalue_form_element']);

  // Add language skos vocabulary.
  $graphs = [
    'language' => 'http://publications.europa.eu/resource/authority/language',
  ];
  \Drupal::service('rdf_skos.skos_graph_configurator')->addGraphs($graphs);

  // Create new base fields.
  $preferred_language_definition = BaseFieldDefinition::create('skos_concept_entity_reference')
    ->setLabel(t('Preferred contact language'))
    ->setSetting('target_type', 'skos_concept')
    ->setSetting('handler', 'default:skos_concept')
    ->setSetting('handler_settings', [
      'target_bundles' => NULL,
      'auto_create' => FALSE,
      'concept_schemes' => [
        'http://publications.europa.eu/resource/authority/language',
      ],
      'concept_subset' => 'contact_languages',
    ])
    ->setDisplayOptions('form', [
      'type' => 'skos_concept_entity_reference_options_select',
      'settings' => [
        'sort' => 'id',
      ],
    ])
    ->setDisplayOptions('view', [
      'weight' => 0,
      'settings' => [
        'link' => FALSE,
      ],
    ]);
  $alternative_language_definition = clone $preferred_language_definition;
  $alternative_language_definition->setLabel(t('Alternative contact language'));

  $update_manager = \Drupal::entityDefinitionUpdateManager();
  $update_manager->installFieldStorageDefinition('oe_preferred_language', 'contact_message', 'oe_contact_forms', $preferred_language_definition);
  $update_manager->installFieldStorageDefinition('oe_alternative_language', 'contact_message', 'oe_contact_forms', $alternative_language_definition);
}

/**
 * Allow contact language fields to use any languages.
 */
function oe_contact_forms_post_update_00003(): void {
  $update_manager = \Drupal::entityDefinitionUpdateManager();
  $fields = \Drupal::service('entity_field.manager')->getBaseFieldDefinitions('contact_message');

  foreach (['oe_preferred_language', 'oe_alternative_language'] as $field_name) {
    $field = $fields[$field_name];
    $setting = $field->getSetting('handler_settings');
    unset($setting['concept_subset']);
    $field->setSetting('handler_settings', $setting);
    $field->setDisplayOptions('form', [
      'type' => 'skos_concept_entity_reference_autocomplete',
    ]);
    $update_manager->updateFieldStorageDefinition($field);
  }
}
