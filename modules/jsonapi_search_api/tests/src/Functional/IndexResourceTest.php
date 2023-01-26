<?php declare(strict_types = 1);

namespace Drupal\Tests\jsonapi_search_api\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\IndexInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\jsonapi\Functional\JsonApiRequestTestTrait;
use Drupal\Tests\jsonapi\Functional\ResourceResponseTestTrait;
use Drupal\Tests\search_api\Functional\ExampleContentTrait;
use Drupal\user\Entity\Role;
use GuzzleHttp\RequestOptions;

/**
 * Tests index resource..
 *
 * @group jsonapi_search_api
 * @coversDefaultClass \Drupal\jsonapi_search_api\Resource\IndexResource
 */
final class IndexResourceTest extends BrowserTestBase {

  use ExampleContentTrait;
  use JsonApiRequestTestTrait;
  use ResourceResponseTestTrait;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  protected static $modules = [
    'node',
    'entity_test',
    'search_api',
    'search_api_test_db',
    'jsonapi_search_api',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
  }

  /**
   * Tests the results contain the index values.
   *
   * @param array $query
   *   The URL query params.
   * @param int $expected_count
   *   The expected document count.
   * @param array $expected_ids
   *   The expected entity IDs.
   * @param array $expected_links_keys
   *   The expected pagination link keys.
   *
   * @dataProvider noQueryDataProvider
   * @dataProvider paginationDataProvider
   * @dataProvider fulltextDataProvider
   * @dataProvider filterDataProvider
   * @dataProvider sortDataProvider
   */
  public function testCollection(array $query, int $expected_count, array $expected_ids, array $expected_links_keys): void {
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';

    $url = Url::fromRoute('jsonapi_search_api.index_database_search_index', [], [
      'query' => $query,
    ]);
    $response = $this->request('GET', $url, $request_options);
    $this->assertSame(200, $response->getStatusCode(), var_export(Json::decode((string) $response->getBody()), TRUE));
    $response_document = Json::decode((string) $response->getBody());
    $this->assertCount($expected_count, $response_document['data'], var_export($response_document, TRUE));
    $ids = array_map(static function (array $data) {
      return $data['attributes']['drupal_internal__id'];
    }, $response_document['data']);
    if (!isset($query['sort'])) {
      sort($ids);
    }
    $this->assertEquals($expected_ids, $ids);
    foreach ($expected_links_keys as $links_key) {
      $this->assertArrayHasKey($links_key, $response_document['links'], var_export($response_document['links'], TRUE));
    }
  }

  /**
   * Tests that the result count is added.
   */
  public function testResultCounting() {
    $original_entity_count = count($this->entities);
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';

    $url = Url::fromRoute('jsonapi_search_api.index_database_search_index', [], []);
    $response = $this->request('GET', $url, $request_options);
    $response_document = Json::decode((string) $response->getBody());
    $this->assertEquals($original_entity_count, $response_document['meta']['count']);

    $this->removeTestEntity(1);
    $response = $this->request('GET', $url, $request_options);
    $response_document = Json::decode((string) $response->getBody());
    $this->assertEquals($original_entity_count - 1, $response_document['meta']['count']);
  }

  /**
   * Tests cache tag invalidation.
   */
  public function testCacheMetadata() {
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $url = Url::fromRoute('jsonapi_search_api.index_database_search_index', [], []);
    $response = $this->request('GET', $url, $request_options);
    $this->assertEquals(['MISS'], $response->getHeader('X-Drupal-Cache'));
    $response = $this->request('GET', $url, $request_options);
    $this->assertEquals(['HIT'], $response->getHeader('X-Drupal-Cache'));

    $entity_id = count($this->entities) + 1;
    $entity = $this->addTestEntity($entity_id, [
      'name' => 'bar',
      'body' => 'test foobar Case',
      'type' => 'item',
    ]);
    $this->indexItems('database_search_index');
    $response = $this->request('GET', $url, $request_options);
    $this->assertEquals(['MISS'], $response->getHeader('X-Drupal-Cache'));
  }

  /**
   * No query data provider.
   *
   * @return \Generator
   *   The data.
   */
  public function noQueryDataProvider(): \Generator {
    yield [
      [],
      5,
      [1, 2, 3, 4, 5],
      [],
    ];
  }

  /**
   * Pagination data provider.
   *
   * @return \Generator
   *   The data.
   */
  public function paginationDataProvider(): \Generator {
    yield [
      [
        'page' => [
          'limit' => 2,
          'offset' => 0,
        ],
      ],
      2,
      [1, 2],
      ['next', 'last'],
    ];
    yield [
      [
        'page' => [
          'limit' => 2,
          'offset' => 2,
        ],
      ],
      2,
      [3, 4],
      ['next', 'last', 'prev', 'first'],
    ];
  }

  /**
   * Fulltext data provider.
   *
   * @return \Generator
   *   The data.
   */
  public function fulltextDataProvider(): \Generator {
    yield [
      [
        'filter' => [
          'fulltext' => 'föö',
        ],
      ],
      1,
      [1],
      [],
    ];
    yield [
      [
        'filter' => [
          'fulltext' => 'foo',
        ],
      ],
      4,
      [1, 2, 4, 5],
      [],
    ];
  }

  /**
   * Filter data provider.
   *
   * @return \Generator
   *   The data.
   */
  public function filterDataProvider(): \Generator {
    yield [
      [
        'filter' => [
          'category' => 'item_category',
        ],
      ],
      2,
      [1, 2],
      [],
    ];
    yield [
      [
        'filter' => [
          'category' => [
            'operator' => '<>',
            'value' => 'item_category',
          ],
        ],
      ],
      3,
      [3, 4, 5],
      [],
    ];
    yield [
      [
        'filter' => [
          'id' => [
            'operator' => '>',
            'value' => '3',
          ],
        ],
      ],
      2,
      [4, 5],
      [],
    ];
    yield [
      [
        'filter' => [
          'category' => [
            'operator' => 'IN',
            'value' => ['item_category', 'article_category'],
          ],
        ],
      ],
      4,
      [1, 2, 4, 5],
      [],
    ];
    yield [
      [
        'filter' => [
          'category' => [
            'operator' => 'NOT IN',
            'value' => ['item_category', 'article_category'],
          ],
        ],
      ],
      1,
      [3],
      [],
    ];
    yield [
      [
        'filter' => [
          'keywords' => 'strawberry',
        ],
      ],
      2,
      [4, 5],
      [],
    ];
    yield [
      [
        'filter' => [
          'keywords' => 'banana',
        ],
      ],
      1,
      [5],
      [],
    ];
    yield [
      [
        'filter' => [
          'keywords' => 'orange',
        ],
      ],
      3,
      [1, 2, 5],
      [],
    ];
  }

  /**
   * Sort data provider.
   *
   * @return \Generator
   *   The data.
   */
  public function sortDataProvider(): \Generator {
    yield [
      [
        'sort' => 'id',
      ],
      5,
      [1, 2, 3, 4, 5],
      [],
    ];
    yield [
      [
        'sort' => '-id',
      ],
      5,
      [5, 4, 3, 2, 1],
      [],
    ];
    yield [
      [
        'sort' => 'search_api_id',
      ],
      5,
      [1, 2, 3, 4, 5],
      [],
    ];
    yield [
      [
        'sort' => '-search_api_id',
      ],
      5,
      [5, 4, 3, 2, 1],
      [],
    ];
    yield [
      [
        'sort' => [
          'sort-id' => [
            'path' => 'id',
            'direction' => 'ASC',
          ],
        ],
      ],
      5,
      [1, 2, 3, 4, 5],
      [],
    ];
    yield [
      [
        'sort' => [
          'sort-id' => [
            'path' => 'id',
            'direction' => 'DESC',
          ],
        ],
      ],
      5,
      [5, 4, 3, 2, 1],
      [],
    ];
  }

}
