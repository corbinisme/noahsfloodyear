<?php

namespace Drupal\jsonapi_search_api_facets\Plugin\facets\url_processor;

use Drupal\Core\Url;
use Drupal\facets\FacetInterface;
use Drupal\facets\Plugin\facets\url_processor\QueryString;
use Drupal\jsonapi\Query\Filter;
use Drupal\jsonapi\Query\OffsetPage;
use Drupal\jsonapi_search_api_facets\Plugin\facets\facet_source\JsonApiFacets;

/**
 * Query string URL processor.
 *
 * @FacetsUrlProcessor(
 *   id = "json_api",
 *   label = @Translation("JSON:API Query string"),
 *   description = @Translation("Process facets generating in JSON:API filter syntax.")
 * )
 */
class JsonApiQueryString extends QueryString {

  /**
   * The query string variable.
   *
   * @var string
   *   The query string variable that holds all the facet information.
   */
  protected $filterKey = Filter::KEY_NAME;

  /**
   * An array of the existing non-facet filters.
   *
   * @var array
   *  The filters.
   */
  protected $originalFilters = [];

  /**
   * {@inheritDoc}
   */
  protected function initializeActiveFilters() {
    $url_parameters = $this->request->query;

    // Get the active facet parameters.
    // @todo can we leverage \Drupal\jsonapi_search_api\Query\Filter
    $active_params = $url_parameters->get($this->filterKey, []);
    $facet_source_id = $this->configuration['facet']->getFacetSourceId();

    // When an invalid parameter is passed in the url, we can't do anything.
    if (!is_array($active_params)) {
      return;
    }
    foreach ($active_params as $param_identifier => $param_value) {
      // Check if this is a complex filter definition.
      if (is_array($param_value)) {
        if (isset($param_value['condition'])) {
          $facet_id = $this->getFacetIdByUrlAlias($param_value['condition']['path'], $facet_source_id);
          // Skip filters that do not target facets.
          if ($facet_id === NULL) {
            $this->originalFilters[$param_identifier] = $param_value;
            continue;
          }
          $this->activeFilters[$facet_id] = [];
          if (isset($param_value['condition']['value'])) {
            foreach ($param_value['condition']['value'] as $condition_value) {
              $this->activeFilters[$facet_id][] = $condition_value;
            }
          }
        }
        // This is not a filter condition.
        else {
          $this->originalFilters[$param_identifier] = $param_value;
        }
      }
      else {
        // Shorthand filter notation.
        $facet_id = $this->getFacetIdByUrlAlias($param_identifier, $facet_source_id);
        if ($facet_id === NULL) {
          $this->originalFilters[$param_identifier] = $param_value;
        }
        else {
          $this->activeFilters[$facet_id] = $param_value;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveItems(FacetInterface $facet) {
    // Get the filter key of the facet.
    if (isset($this->activeFilters[$facet->id()])) {
      $active_filters = $this->activeFilters[$facet->id()];
      if (is_array($active_filters)) {
        $facet->setActiveItems($active_filters);
      }
      else {
        $facet->setActiveItem($active_filters);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildUrls(FacetInterface $facet, array $results) {
    // No results are found for this facet, so don't try to create urls.
    if (empty($results)) {
      return [];
    }
    $facet_source = $facet->getFacetSource();
    // This URL processor only works with JSON:API Facets.
    if (!$facet_source instanceof JsonApiFacets) {
      return [];
    }

    // First get the current list of get parameters.
    $get_params = $this->request->query;

    // When adding/removing a filter the number of pages may have changed,
    // possibly resulting in an invalid page parameter.
    if ($get_params->has(OffsetPage::KEY_NAME)) {
      $page_params = $get_params->get(OffsetPage::KEY_NAME);
      unset($page_params[OffsetPage::OFFSET_KEY]);
      $get_params->set(OffsetPage::KEY_NAME, $page_params);
    }

    // Set the url alias from the facet object.
    $this->urlAlias = $facet->getUrlAlias();

    $facet_source_path = $facet->getFacetSource()->getPath();
    $request = $this->getRequestByFacetSourcePath($facet_source_path);
    $requestUrl = $this->getUrlForRequest($facet_source_path, $request);

    $original_filter_params = [];
    foreach ($this->getActiveFilters() as $facet_id => $value) {
      $facet_url_alias = $this->getUrlAliasByFacetId($facet_id, $facet->getFacetSourceId());
      $this->addToFilter($facet_url_alias, $value, $original_filter_params);
    }

    foreach ($results as &$result) {
      // Reset the URL for each result.
      $url = clone $requestUrl;

      // Sets the url for children.
      if ($children = $result->getChildren()) {
        $this->buildUrls($facet, $children);
      }

      $filter_string = $result->getRawValue();
      $result_get_params = clone $get_params;

      $filter_params = $original_filter_params;
      if ($filter_string !== NULL) {
        $this->addToFilter($this->urlAlias, $filter_string, $filter_params);
      }

      if ($result->isActive()) {
        // Facets' itself does not allow hierarchical settings to be configured
        // on anything that's not a view as of current. With that in mind this
        // code -will- still operate like the parent it inherits from if the
        // bogus check is bypassed.
        // @see: https://www.drupal.org/project/facets/issues/3157720
        if ($facet->getEnableParentWhenChildGetsDisabled() && $facet->getUseHierarchy()) {
          // Enable parent id again if exists.
          $parent_ids = $facet->getHierarchyInstance()->getParentIds($result->getRawValue());
          if (isset($parent_ids[0]) && $parent_ids[0]) {
            // Get the parents children.
            $child_ids = $facet->getHierarchyInstance()->getNestedChildIds($parent_ids[0]);

            // Check if there are active siblings.
            $active_sibling = FALSE;
            if ($child_ids) {
              foreach ($results as $result2) {
                if ($result2->isActive() && $result2->getRawValue() != $result->getRawValue() && in_array($result2->getRawValue(), $child_ids)) {
                  $active_sibling = TRUE;
                  continue;
                }
              }
            }
            if (!$active_sibling) {
              $this->addToFilter($this->urlAlias, $parent_ids[0], $filter_params);
            }
          }
        }
      }
      // If the value is not active, add the filter string.
      else {
        if ($facet->getUseHierarchy()) {
          // If hierarchy is active, unset parent trail and every child when
          // building the enable-link to ensure those are not enabled anymore.
          $parent_ids = $facet->getHierarchyInstance()->getParentIds($result->getRawValue());
          $child_ids = $facet->getHierarchyInstance()->getNestedChildIds($result->getRawValue());
          $parents_and_child_ids = array_merge($parent_ids, $child_ids);
          foreach ($parents_and_child_ids as $id) {
            $filter_params = array_diff($filter_params, [$id]);
          }
        }
        // Exclude currently active results from the filter params if we are in
        // the show_only_one_result mode.
        if ($facet->getShowOnlyOneResult()) {
          foreach ($results as $result2) {
            if ($result2->isActive()) {
              $active_filter_string = $result2->getRawValue();
              foreach ($filter_params as $filter_key => $filter_values) {
                if (in_array($active_filter_string, $filter_values)) {
                  $filter_params[$filter_key] = array_diff($filter_values, [$active_filter_string]);
                }
              }
            }
          }
        }
      }

      // Rewrite into JSON:API style filters now, start by removing all
      // existing filters as they will be re-created.
      $result_get_params->remove('filter');
      $result_get_params->set('filter', $this->originalFilters);
      foreach ($filter_params as $filter_path => $values) {
        $existing_params = $result_get_params->all();
        // Simple facets can be done.
        if (count($values) === 1) {
          $value = reset($values);
          $params = array_merge_recursive($existing_params, [
            'filter' => [
              $filter_path => $value,
            ],
          ]);
        }
        else {
          // Build up "fancy" facets, handle other ops (NOT)?.
          $filter_key = strtr('!field-facet', ['!field' => $filter_path]);
          $params = array_merge_recursive($existing_params, [
            'filter' => [
              $filter_key => [
                'condition' => [
                  'path' => $filter_path,
                  'operator' => 'IN',
                  'value' => [],
                ],
              ],
            ],
          ]);
          foreach ($values as $value) {
            $params['filter'][$filter_key]['condition']['value'][] = $value;
          }
        }
        $result_get_params->add($params);
      }
      if ($result_get_params->all() !== [$this->filterKey => []]) {
        $new_url_params = $result_get_params->all();
        // Set the new url parameters.
        $url->setOption('query', $new_url_params);
      }
      $result->setUrl($url);
    }
    return $results;
  }

  /**
   * Adds a facet value to the active filter parameters.
   *
   * @param string $key
   *   The filter key.
   * @param string|array $values
   *   The value.
   * @param array $filter_params
   *   The filter params.
   */
  private function addToFilter($key, $values, array &$filter_params) {
    if (!isset($filter_params[$key])) {
      $filter_params[$key] = [];
    }
    if (!is_array($values)) {
      $values = [$values];
    }
    foreach ($values as $value) {
      if (!in_array($value, $filter_params[$key], TRUE)) {
        $filter_params[$key][] = $value;
      }
    }
  }

}
