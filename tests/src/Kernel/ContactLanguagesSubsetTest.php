<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_contact_forms\Kernel;

use Drupal\rdf_skos\Plugin\EntityReferenceSelection\SkosConceptSelection;
use Drupal\Tests\rdf_skos\Traits\SkosImportTrait;
use Drupal\Tests\sparql_entity_storage\Kernel\SparqlKernelTestBase;

/**
 * Test "Contact languages" concept subset.
 */
class ContactLanguagesSubsetTest extends SparqlKernelTestBase {

  use SkosImportTrait;

  /**
   * List of EU languages.
   */
  const EU_LANGUAGES = [
    'http://publications.europa.eu/resource/authority/language/BUL',
    'http://publications.europa.eu/resource/authority/language/CES',
    'http://publications.europa.eu/resource/authority/language/DAN',
    'http://publications.europa.eu/resource/authority/language/DEU',
    'http://publications.europa.eu/resource/authority/language/ELL',
    'http://publications.europa.eu/resource/authority/language/ENG',
    'http://publications.europa.eu/resource/authority/language/EST',
    'http://publications.europa.eu/resource/authority/language/FIN',
    'http://publications.europa.eu/resource/authority/language/FRA',
    'http://publications.europa.eu/resource/authority/language/GLE',
    'http://publications.europa.eu/resource/authority/language/HRV',
    'http://publications.europa.eu/resource/authority/language/HUN',
    'http://publications.europa.eu/resource/authority/language/ITA',
    'http://publications.europa.eu/resource/authority/language/LAV',
    'http://publications.europa.eu/resource/authority/language/LIT',
    'http://publications.europa.eu/resource/authority/language/MLT',
    'http://publications.europa.eu/resource/authority/language/NLD',
    'http://publications.europa.eu/resource/authority/language/POL',
    'http://publications.europa.eu/resource/authority/language/POR',
    'http://publications.europa.eu/resource/authority/language/RON',
    'http://publications.europa.eu/resource/authority/language/SLK',
    'http://publications.europa.eu/resource/authority/language/SLV',
    'http://publications.europa.eu/resource/authority/language/SPA',
    'http://publications.europa.eu/resource/authority/language/SWE',
  ];

  /**
   * List of non-EU languages.
   */
  const NON_EU_LANGUAGES = [
    'http://publications.europa.eu/resource/authority/language/RUS',
    'http://publications.europa.eu/resource/authority/language/SQI',
    'http://publications.europa.eu/resource/authority/language/TUR',
    'http://publications.europa.eu/resource/authority/language/URD',
    'http://publications.europa.eu/resource/authority/language/VIE',
    'http://publications.europa.eu/resource/authority/language/ZHO',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'contact',
    'rdf_skos',
    'oe_contact_forms',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $graphs = [
      'language' => 'http://publications.europa.eu/resource/authority/language',
    ];
    \Drupal::service('rdf_skos.skos_graph_configurator')->addGraphs($graphs);
  }

  /**
   * Tests that only EU languages are allowed in "Contact languages" subset.
   */
  public function testContactLanguagesSubset(): void {
    $configuration = [
      'target_type' => 'skos_concept',
      'concept_schemes' => ['http://publications.europa.eu/resource/authority/language'],
      'concept_subset' => 'contact_languages',
    ];
    $selection = SkosConceptSelection::create($this->container, $configuration, 'default:skos_concept', []);
    $ids = array_merge(self::EU_LANGUAGES, self::NON_EU_LANGUAGES);
    $result = array_values($selection->validateReferenceableEntities($ids));
    $this->assertEquals(self::EU_LANGUAGES, $result);
  }

}
