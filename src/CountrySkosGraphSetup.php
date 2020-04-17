<?php

declare(strict_types = 1);

namespace Drupal\oe_contact_forms;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service used to set up the Country SKOS graph.
 */
class CountrySkosGraphSetup {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * PublicationsOfficeSkosGraphSetup constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * Gets the graph information for the OP vocabularies.
   *
   * @todo instead of hardcoding, use the content layer to determine these.
   */
  protected function getGraphInfo(): array {
    return [
      'country' => 'http://publications.europa.eu/resource/authority/country',
    ];
  }

  /**
   * Sets up the graphs.
   */
  public function setup(): void {
    $graphs = $this->getGraphInfo();
    $config = [];
    foreach ($graphs as $name => $graph) {
      $config['skos_concept_scheme'][] = [
        'name' => $name,
        'uri' => $graph,
      ];

      $config['skos_concept'][] = [
        'name' => $name,
        'uri' => $graph,
      ];
    }

    $this->configFactory->getEditable('rdf_skos.graphs')->set('entity_types', $config)->save();
  }

}
