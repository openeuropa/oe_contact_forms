<?php

/**
 * @file
 * Post update functions for OpenEuropa Contact Forms module.
 */

declare(strict_types = 1);

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
  $graphs = [
    'language' => 'http://publications.europa.eu/resource/authority/language',
  ];
  \Drupal::service('rdf_skos.skos_graph_configurator')->addGraphs($graphs);

  // Create new base fields.
  $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  $storage_definition = BaseFieldDefinition::create('skos_concept_entity_reference')
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
        'sort' => 'label',
      ],
    ])
    ->setDisplayOptions('view', [
      'weight' => 0,
      'settings' => [
        'link' => FALSE,
      ],
    ]);
  $definition_update_manager->installFieldStorageDefinition('oe_preferred_language', 'contact_message', 'oe_contact_forms', $storage_definition);

  $storage_definition = BaseFieldDefinition::create('skos_concept_entity_reference')
    ->setLabel(t('Alternative contact language'))
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
        'sort' => 'label',
      ],
    ])
    ->setDisplayOptions('view', [
      'weight' => 0,
      'settings' => [
        'link' => FALSE,
      ],
    ]);
  $definition_update_manager->installFieldStorageDefinition('oe_alternative_language', 'contact_message', 'oe_contact_forms', $storage_definition);
}
