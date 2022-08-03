<?php

namespace Drupal\jsonapi_search_api_facets\Plugin\facets\facet_source;

use Drupal\Component\Plugin\PluginBase;
use Drupal\facets\FacetSource\FacetSourceDeriverBase;

/**
 * Derives a facet source plugin definition for every index.
 */
class JsonApiFacetsDeriver extends FacetSourceDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $derivatives = [];
    foreach ($this->entityTypeManager->getStorage('search_api_index')->getQuery()->execute() as $index) {
      // Only derive for an index that supports facets.
      if ($this->entityTypeManager->getStorage('search_api_index')->load($index)->getServerInstance()->supportsFeature('search_api_facets')) {
        $derivatives[$index] = [
          'id' => $base_plugin_definition['id'] . PluginBase::DERIVATIVE_SEPARATOR . $index,
          'display_id' => strtr('jsonapi_search_api_facets_!index', ['!index' => $index]),
          'label' => $this->t('JSON:API Search API Facets: @index', ['@index' => $index]),
          'index' => $index,
        ] + $base_plugin_definition;
      }
    }
    return $derivatives;
  }

}
