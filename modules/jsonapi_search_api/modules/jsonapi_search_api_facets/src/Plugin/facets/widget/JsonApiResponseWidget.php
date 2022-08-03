<?php

namespace Drupal\jsonapi_search_api_facets\Plugin\facets\widget;

use Drupal\facets\FacetInterface;
use Drupal\facets\Result\ResultInterface;
use Drupal\facets\Widget\WidgetPluginBase;

/**
 * A simple widget class that returns for inclusion in JSON:API Search API.
 *
 * @FacetsWidget(
 *   id = "jsonapi_search_api",
 *   label = @Translation("JSON:API Search API"),
 *   description = @Translation("A widget that builds an array with results. Used only for integrating into JSON:API Search API."),
 * )
 *
 * @note: This widget is almost identical to ArrayWidget except changing how
 * URLs are being generated as to not leak cacheable metadata.
 */
class JsonApiResponseWidget extends WidgetPluginBase {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    $build = [
      'id' => $facet->id(),
      'label' => $facet->getName(),
      'path' => $facet->getUrlAlias(),
      'terms' => [],
    ];

    $configuration = $facet->getWidget();
    $this->showNumbers = empty($configuration['show_numbers']) ? FALSE : (bool) $configuration['show_numbers'];
    foreach ($facet->getResults() as $result) {
      if ($result->getUrl() === NULL) {
        $build['terms'][] = $this->generateValues($result);
      }
      else {
        $build['terms'][] = $this->buildListItems($facet, $result);
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildListItems(FacetInterface $facet, ResultInterface $result) {
    if ($children = $result->getChildren()) {
      $items = $this->prepare($result);

      $children_markup = [];
      foreach ($children as $child) {
        $children_markup[] = $this->buildChildren($child);
      }

      $items['children'] = [$children_markup];

    }
    else {
      $items = $this->prepare($result);
    }
    return $items;
  }

  /**
   * Prepares the URL and values for the facet.
   *
   * @param \Drupal\facets\Result\ResultInterface $result
   *   A result item.
   *
   * @return array
   *   The results.
   */
  protected function prepare(ResultInterface $result) {
    $values = $this->generateValues($result);

    $url = $result->getUrl();
    if ($url === NULL) {
      $facet_values = $values;
    }
    else {
      $facet_values['url'] = $url->setAbsolute(TRUE)->toString(TRUE)->getGeneratedUrl();
      $facet_values['values'] = $values;
    }

    return $facet_values;
  }

  /**
   * Builds an array for children results.
   *
   * @param \Drupal\facets\Result\ResultInterface $child
   *   A result item.
   *
   * @return array
   *   An array with the results.
   */
  protected function buildChildren(ResultInterface $child) {
    $values = $this->generateValues($child);

    $url = $child->getUrl();
    if ($url !== NULL) {
      $facet_values['url'] = $url->setAbsolute(TRUE)->toString(TRUE)->getGeneratedUrl();
      $facet_values['values'] = $values;
    }
    else {
      $facet_values = $values;
    }

    return $facet_values;
  }

  /**
   * Generates the value and the url.
   *
   * @param \Drupal\facets\Result\ResultInterface $result
   *   The result to extract the values.
   *
   * @return array
   *   The values.
   */
  protected function generateValues(ResultInterface $result) {
    $values = [
      'value' => $result->getRawValue(),
      'label' => $result->getDisplayValue(),
      'active' => $result->isActive(),
    ];

    if ($this->configuration['show_numbers']) {
      $values['count'] = (int) $result->getCount();
    }

    return $values;
  }

}
