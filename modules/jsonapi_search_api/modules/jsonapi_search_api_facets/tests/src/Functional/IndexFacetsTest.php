<?php

namespace Drupal\Tests\jsonapi_search_api_facets\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\facets\Entity\Facet;
use Drupal\facets\Entity\FacetSource;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\IndexInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\jsonapi\Functional\JsonApiRequestTestTrait;
use Drupal\Tests\search_api\Functional\ExampleContentTrait;
use Drupal\user\Entity\Role;
use GuzzleHttp\RequestOptions;

/**
 * Tests searching with facets.
 *
 * @group jsonapi_search_api_facets
 * @group jsonapi_search_api
 * @requires module facets
 */
final class IndexFacetsTest extends BrowserTestBase {
  use ExampleContentTrait;
  use JsonApiRequestTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'entity_test',
    'search_api',
    'search_api_test_db',
    'jsonapi_search_api',
    'jsonapi_search_api_facets',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    // Set up example structure and content and populate the test index with
    // that content.
    $this->setUpExampleStructure();
    $this->insertExampleContent();

    $index = Index::load('database_search_index');
    assert($index instanceof IndexInterface);
    $this->container
      ->get('search_api.index_task_manager')
      ->addItemsAll($index);
    $index->indexItems();

    $this->container->get('router.builder')->rebuildIfNeeded();

    $this->grantPermissions(Role::load(Role::ANONYMOUS_ID), [
      'view test entity',
    ]);

    FacetSource::create([
      'id' => 'jsonapi_search_api_facets__database_search_index',
      'name' => 'jsonapi_search_api_facets:database_search_index',
    ])->save();
  }

  /**
   * Creates a facet.
   *
   * @param string $field_name
   *   The index field name.
   * @param string $name
   *   The facet name.
   * @param string $url_alias
   *   The facet URL alias.
   * @param string $query_operator
   *   The query operator, 'or' or 'and'.
   * @param bool $show_numbers
   *   Whether to show numbers.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createFacet(string $field_name, string $name, string $url_alias, string $query_operator, bool $show_numbers): void {
    assert($query_operator === 'and' || $query_operator === 'or', '$query_operator must be `or` or `and`');
    Facet::create([
      'id' => $field_name,
      'name' => $name,
      'url_alias' => $url_alias,
      'query_operator' => $query_operator,
      'field_identifier' => $field_name,
      'facet_source_id' => 'jsonapi_search_api_facets:database_search_index',
      'widget' => [
        'type' => 'jsonapi_search_api',
        'config' => [
          'show_numbers' => $show_numbers,
        ],
      ],
      'empty_behavior' => ['behavior' => 'none'],
      'processor_configs' => [
        'url_processor_handler' => [
          'processor_id' => 'url_processor_handler',
          'weights' => ['pre_query' => -10, 'build' => -10],
          'settings' => [],
        ],
      ],
    ])->save();
  }

  /**
   * Asserts the URL filter parameters for a facet term.
   *
   * @param array $facet_term
   *   The facet term.
   * @param array $expected_filters
   *   The expected filter query parameters.
   */
  private function assertUrlFilterParams(array $facet_term, array $expected_filters) {
    $this->assertArrayHasKey('url', $facet_term);
    $filter_params = [];
    parse_str(parse_url($facet_term['url'], PHP_URL_QUERY), $filter_params);

    // Make expanded filters are sorted, so we don't fail on ordering.
    foreach ($expected_filters as $filter_name => $expected_filter) {
      if (is_array($expected_filter)) {
        sort($expected_filters[$filter_name]['condition']['value']);
      }
    }
    foreach ($filter_params['filter'] as $filter_name => $expected_filter) {
      if (is_array($expected_filter)) {
        sort($filter_params['filter'][$filter_name]['condition']['value']);
      }
    }

    $this->assertEquals(['filter' => $expected_filters], $filter_params, var_export($filter_params, TRUE));
  }

  /**
   * Test facet data.
   *
   * @dataProvider dataForFacets
   */
  public function testWithFacets(
    array $faceted_fields,
    bool $show_numbers,
    array $facet_query,
    int $expected_filtered_count
  ) {
    foreach ($faceted_fields as $field_name => $faceted_field) {
      $query_operator = $faceted_field['query_operator'] ?? 'or';
      $facet_filter_path = $faceted_field['alias'] ?? $field_name;
      $this->createFacet($field_name, $faceted_field['name'], $facet_filter_path, $query_operator, $show_numbers);
    }

    $url = Url::fromRoute('jsonapi_search_api.index_database_search_index');
    $data = $this->doRequest($url);
    $this->assertArrayHasKey('facets', $data['meta']);
    foreach ($data['meta']['facets'] as $facet) {
      $this->assertArrayHasKey($facet['id'], $faceted_fields);
      $faceted_field_info = $faceted_fields[$facet['id']];
      $this->assertEquals($faceted_field_info['name'], $facet['label'], var_export($facet, TRUE));

      $first_term = $facet['terms'][0];
      $this->assertUrlFilterParams($first_term, [$facet['path'] => $first_term['values']['value']]);
      $this->assertEquals($show_numbers, isset($first_term['values']['count']), var_export($facet, TRUE));
    }

    $url->setOption('query', ['filter' => $facet_query]);
    $data = $this->doRequest($url);
    $this->assertCount($expected_filtered_count, $data['data'], var_export($data, TRUE));

    // Check that applied facets are marked as active.
    $filter_condition_paths = [];
    foreach ($facet_query as $filter_name => $filter_condition) {
      $filter_condition_paths[] = is_array($filter_condition) ? $filter_condition['condition']['path'] : $filter_name;
    }
    $applied_facets = array_filter($data['meta']['facets'], static function (array $facet) use ($filter_condition_paths) {
      return in_array($facet['path'], $filter_condition_paths, TRUE);
    });
    $this->assertCount(count($filter_condition_paths), $applied_facets);
    foreach ($applied_facets as $facet) {
      $active_terms = array_filter($facet['terms'], static function (array $term) {
        return $term['values']['active'] === TRUE;
      });
      $this->assertNotCount(0, $active_terms, var_export($facet['terms'], TRUE));
      $first_term = $facet['terms'][0];

      $expected_filter_query = $facet_query;
      // Remove the current facet condition from the existing filters, so that
      // we may assert it has been updated.
      foreach ($expected_filter_query as $filter_name => $filter_condition) {
        $filter_condition_path = is_array($filter_condition) ? $filter_condition['condition']['path'] : $filter_name;
        if ($filter_condition_path === $facet['path']) {
          unset($expected_filter_query[$filter_name]);
        }
      }

      // The facet URL should contain the active terms, and the first time.
      // The first term may be an active term, so we filter it out when
      // determing the values.
      $expected_filter_query_facets = $active_terms;
      $expected_filter_query_facets[] = $first_term;
      $expected_filter_query_values = array_unique(array_values(array_map(static function (array $item) {
        return $item['values']['value'];
      }, $expected_filter_query_facets)));

      if (count($expected_filter_query_values) > 1) {
        $expected_filter_query[strtr('!field-facet', ['!field' => $facet['path']])] = [
          'condition' => [
            'path' => $facet['path'],
            'operator' => 'IN',
            'value' => $expected_filter_query_values,
          ],
        ];
      }
      else {
        $expected_filter_query[$facet['path']] = $first_term['values']['value'];
      }

      $this->assertUrlFilterParams($first_term, $expected_filter_query);
    }
  }

  /**
   * Tests facets are rendeted properly when empty.
   */
  public function testEmptyFacets() {
    $this->createFacet('keywords', 'Keywords', 'keywords', 'or', FALSE);
    $this->createFacet('category', 'Category', 'category', 'or', FALSE);
    $url = Url::fromRoute('jsonapi_search_api.index_database_search_index', [], [
      'query' => [
        'filter' => [
          'name' => 'does not exist',
        ],
      ],
    ]);
    $data = $this->doRequest($url);
    $this->assertCount(0, $data['data']);
    $this->assertArrayHasKey('facets', $data['meta'], var_export($data, TRUE));
    $facet_ids = array_map(static function (array $facet) {
      return $facet['id'];
    }, $data['meta']['facets']);
    $this->assertEquals(['category', 'keywords'], $facet_ids);
    foreach ($data['meta']['facets'] as $facet) {
      $this->assertEquals([
        'id',
        'label',
        'path',
        'terms',
      ], array_keys($facet));
      $this->assertCount(0, $facet['terms'], var_export($facet, TRUE));
    }
  }

  /**
   * Tests that existing filters are preserved in facet term URLs.
   */
  public function testWithExistingFilter() {
    $this->createFacet('keywords', 'Keywords', 'keywords', 'or', FALSE);
    $filter = [
      'category' => 'item_category',
      'keywords-facet' => [
        'condition' => [
          'path' => 'keywords',
          'operator' => 'IN',
          'value' => ['grape', 'strawberry'],
        ],
      ],
    ];
    $url = Url::fromRoute('jsonapi_search_api.index_database_search_index', [], [
      'query' => [
        'filter' => $filter,
      ],
    ]);
    $data = $this->doRequest($url);
    $this->assertCount(1, $data['data']);
    $keywords_facet = $data['meta']['facets'][0];
    $first_term = reset($keywords_facet['terms']);
    $first_term_url_query = [];
    parse_str(parse_url($first_term['url'], PHP_URL_QUERY), $first_term_url_query);
    $this->assertEquals($filter, $first_term_url_query['filter']);
  }

  /**
   * Daa provider for the test.
   *
   * @return \Generator
   *   The test data.
   */
  public function dataForFacets(): \Generator {
    // Baseline with no query filter.
    yield [
      [
        'keywords' => [
          'name' => 'Keywords',
          'query_operator' => 'or',
        ],
      ],
      FALSE,
      [],
      5,
    ];
    yield [
      [
        'keywords' => [
          'name' => 'Keywords',
          'query_operator' => 'or',
        ],
      ],
      FALSE,
      ['keywords' => 'grape'],
      3,
    ];
    yield [
      [
        'keywords' => [
          'name' => 'Keywords',
          'alias' => 'f_keywords',
          'query_operator' => 'or',
        ],
      ],
      FALSE,
      ['f_keywords' => 'grape'],
      3,
    ];
    yield [
      [
        'keywords' => [
          'name' => 'Keywords',
          'query_operator' => 'or',
        ],
      ],
      FALSE,
      [
        'keywords-filter' => [
          'condition' => [
            'path' => 'keywords',
            'operator' => 'IN',
            'value' => ['grape', 'strawberry'],
          ],
        ],
      ],
      3,
    ];
    yield [
      [
        'keywords' => [
          'name' => 'Keywords',
          'query_operator' => 'and',
        ],
      ],
      FALSE,
      ['keywords' => 'grape'],
      3,
    ];
    yield [
      [
        'keywords' => [
          'name' => 'Keywords',
          'query_operator' => 'and',
        ],
      ],
      FALSE,
      [
        'keywords-filter' => [
          'condition' => [
            'path' => 'keywords',
            'operator' => 'IN',
            'value' => ['grape', 'strawberry', 'banana'],
          ],
        ],
      ],
      1,
    ];
    yield [
      [
        'keywords' => [
          'name' => 'Keywords',
          'query_operator' => 'or',
        ],
      ],
      TRUE,
      ['keywords' => 'apple'],
      2,
    ];
    yield [
      [
        'category' => [
          'name' => 'Category',
          'query_operator' => 'or',
        ],
        'keywords' => [
          'name' => 'Keywords',
          'query_operator' => 'or',
        ],
      ],
      FALSE,
      [
        'keywords' => 'apple',
        'category' => 'item_category',
      ],
      1,
    ];
    yield [
      [
        'category' => [
          'name' => 'Category',
          'query_operator' => 'and',
        ],
        'keywords' => [
          'name' => 'Keywords',
          'query_operator' => 'and',
        ],
      ],
      FALSE,
      [
        'keywords' => 'grape',
        'category' => 'item_category',
      ],
      1,
    ];
    yield [
      [
        'category' => [
          'name' => 'Category',
          'query_operator' => 'or',
        ],
        'keywords' => [
          'name' => 'Keywords',
          'query_operator' => 'or',
        ],
      ],
      FALSE,
      [
        'keywords' => 'banana',
        'category' => 'article_category',
      ],
      1,
    ];
  }

  /**
   * Do a request.
   *
   * @param \Drupal\Core\Url $url
   *   The URL.
   *
   * @return array
   *   The decoded response JSON.
   */
  private function doRequest(Url $url) {
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $response = $this->request('GET', $url, $request_options);
    $this->assertSame(200, $response->getStatusCode(), var_export(Json::decode((string) $response->getBody()), TRUE));
    return Json::decode((string) $response->getBody());
  }

}
