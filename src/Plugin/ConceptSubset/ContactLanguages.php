<?php

declare(strict_types = 1);

namespace Drupal\oe_contact_forms\Plugin\ConceptSubset;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\rdf_skos\ConceptSubsetPluginBase;

/**
 * Creates a subset of the languages vocabulary.
 *
 * @ConceptSubset(
 *   id = "contact_languages",
 *   label = @Translation("Contact Languages"),
 *   description = @Translation("Languages to be used for on the contact form."),
 *   concept_schemes = {
 *     "http://publications.europa.eu/resource/authority/language"
 *   }
 * )
 */
class ContactLanguages extends ConceptSubsetPluginBase {

  /**
   * {@inheritdoc}
   */
  public function alterQuery(QueryInterface $query, $match_operator, array $concept_schemes = [], string $match = NULL): void {
    $languages = [
      'Bulgarian' => 'http://publications.europa.eu/resource/authority/language/BUL',
      'Spanish' => 'http://publications.europa.eu/resource/authority/language/SPA',
      'Czech' => 'http://publications.europa.eu/resource/authority/language/CES',
      'Danish' => 'http://publications.europa.eu/resource/authority/language/DAN',
      'German' => 'http://publications.europa.eu/resource/authority/language/DEU',
      'Estonian' => 'http://publications.europa.eu/resource/authority/language/EST',
      'Greek' => 'http://publications.europa.eu/resource/authority/language/ELL',
      'English' => 'http://publications.europa.eu/resource/authority/language/ENG',
      'French' => 'http://publications.europa.eu/resource/authority/language/FRA',
      'Irish' => 'http://publications.europa.eu/resource/authority/language/GLE',
      'Croatian' => 'http://publications.europa.eu/resource/authority/language/HRV',
      'Italian' => 'http://publications.europa.eu/resource/authority/language/ITA',
      'Latvian' => 'http://publications.europa.eu/resource/authority/language/LAV',
      'Lithuanian' => 'http://publications.europa.eu/resource/authority/language/LIT',
      'Hungarian' => 'http://publications.europa.eu/resource/authority/language/HUN',
      'Maltese' => 'http://publications.europa.eu/resource/authority/language/MLT',
      'Dutch' => 'http://publications.europa.eu/resource/authority/language/NLD',
      'Polish' => 'http://publications.europa.eu/resource/authority/language/POL',
      'Portuguese' => 'http://publications.europa.eu/resource/authority/language/POR',
      'Romanian' => 'http://publications.europa.eu/resource/authority/language/RON',
      'Slovak' => 'http://publications.europa.eu/resource/authority/language/SLK',
      'Slovenian' => 'http://publications.europa.eu/resource/authority/language/SLV',
      'Finnish' => 'http://publications.europa.eu/resource/authority/language/FIN',
      'Swedish' => 'http://publications.europa.eu/resource/authority/language/SWE',
    ];

    $query->condition('id', array_values($languages), 'IN');
  }

}
