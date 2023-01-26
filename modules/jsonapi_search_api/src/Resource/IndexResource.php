<?php declare(strict_types = 1);

namespace Drupal\jsonapi_search_api\Resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Http\Exception\CacheableBadRequestHttpException;
use Drupal\Core\Url;
use Drupal\jsonapi\JsonApiResource\Link;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\Query\OffsetPage;
use Drupal\jsonapi\Query\Sort;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi_resources\Resource\EntityResourceBase;
use Drupal\jsonapi_search_api\Event\AddSearchMetaEvent;
use Drupal\jsonapi_search_api\Event\Events;
use Drupal\jsonapi_search_api\Query\Filter;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\ParseMode\ParseModeInterface;
use Drupal\search_api\ParseMode\ParseModePluginManager;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\SearchApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * JSON:API Resource to return Search API index results.
 */
final class IndexResource extends EntityResourceBase implements ContainerInjectionInterface {

  /**
   * The parse mode manager.
   *
   * @var \Drupal\search_api\ParseMode\ParseModePluginManager
   */
  private $parseModeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private $eventDispatcher;

  /**
   * Constructs a new IndexResource object.
   *
   * @param \Drupal\search_api\ParseMode\ParseModePluginManager $parse_mode_manager
   *   The parse mode manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(ParseModePluginManager $parse_mode_manager, EventDispatcherInterface $event_dispatcher) {
    $this->parseModeManager = $parse_mode_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): IndexResource {
    return new self(
      $container->get('plugin.manager.search_api.parse_mode'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Process the resource request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\search_api\IndexInterface $index
   *   The index.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\search_api\SearchApiException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function process(Request $request, IndexInterface $index): ResourceResponse {
    $cacheability = new CacheableMetadata();
    // Ensure that different pages will be cached separately.
    $cacheability->addCacheContexts(['url.query_args:page']);
    $cacheability->addCacheContexts(['url.query_args:filter']);
    $cacheability->addCacheContexts(['url.query_args:sort']);
    // Ensure changes to the index invalidate cache.
    $cacheability->addCacheableDependency($index);
    // Make sure the index list cache tag is present.
    $cacheability->addCacheTags(['search_api_list:' . $index->id()]);

    $query = $index->query();

    // Set the search ID so we can identify that these queries are coming from
    // JSON:API.
    $query->setSearchId(strtr('jsonapi_search_api:!index', ['!index' => $index->id()]));
    // Derive any pagination options from the query params or use defaults.
    $pagination = $this->getPagination($request);
    if ($pagination->getSize() <= 0) {
      throw new CacheableBadRequestHttpException($cacheability, sprintf('The page size needs to be a positive integer.'));
    }
    $query->range($pagination->getOffset(), $pagination->getSize());

    if ($request->query->has(Filter::KEY_NAME)) {
      $this->applyFiltersToQuery($request, $query, $cacheability);
    }

    if ($request->query->has('sort')) {
      $this->applySortingToQuery($request, $query, $cacheability);
    }

    // Get the results and convert to JSON:API resource object data.
    try {
      $results = $query->execute();
    }
    catch (SearchApiException $exception) {
      throw new CacheableBadRequestHttpException($cacheability, $exception->getMessage());
    }
    // Load all entities at once, for better performance.
    $results->preLoadResultItems();
    $result_entities = array_map(static function (ItemInterface $item) {
      return $item->getOriginalObject()->getValue();
    }, $results->getResultItems());
    $primary_data = $this->createCollectionDataFromEntities(array_values($result_entities));
    $primary_data->setTotalCount((int) $results->getResultCount());
    $pager_links = $this->getPagerLinks($request, $pagination, $primary_data->getTotalCount(), count($result_entities));

    // @todo remove after https://www.drupal.org/project/jsonapi_resources/issues/3120437
    $meta = ['count' => $primary_data->getTotalCount()];

    // Dispatch an event to allow other modules to modify the meta.
    $event = new AddSearchMetaEvent($query, $results, $meta);
    $meta = $this->eventDispatcher->dispatch($event, Events::ADD_SEARCH_META)->getMeta();
    $response = $this->createJsonapiResponse($primary_data, $request, 200, [], $pager_links, $meta);
    $response->addCacheableDependency($cacheability);
    return $response;
  }

  /**
   * Apply filters to the index query.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The query.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheability
   *   The cache metadata.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function applyFiltersToQuery(Request $request, QueryInterface $query, CacheableMetadata $cacheability): void {
    $parse_mode = $this->parseModeManager->createInstance('terms');
    assert($parse_mode instanceof ParseModeInterface);
    $query->setParseMode($parse_mode);

    $filter = $request->query->all(Filter::KEY_NAME);
    if (isset($filter['fulltext'])) {
      $query->keys($filter['fulltext']);
      unset($filter['fulltext']);
    }
    try {
      $filter = Filter::createFromQueryParameter($filter);
      $query->addConditionGroup($filter->queryCondition($query));
    }
    catch (\Exception $exception) {
      throw new CacheableBadRequestHttpException($cacheability, $exception->getMessage());
    }
  }

  /**
   * Apply sorting to the index query.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The query.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheability
   *   The cache metadata.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function applySortingToQuery(Request $request, QueryInterface $query, CacheableMetadata $cacheability): void {
    $params = $request->query->all();
    $sort = Sort::createFromQueryParameter($params['sort']);

    foreach ($sort->fields() as $field) {
      $query->sort($field['path'], $field['direction']);
    }
  }

  /**
   * Get pagination for the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\jsonapi\Query\OffsetPage
   *   The pagination object.
   */
  private function getPagination(Request $request): OffsetPage {
    return $request->query->has('page')
      ? OffsetPage::createFromQueryParameter($request->query->all('page'))
      : new OffsetPage(OffsetPage::DEFAULT_OFFSET, OffsetPage::SIZE_MAX);
  }

  /**
   * Get pager links.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\jsonapi\Query\OffsetPage $pagination
   *   The pagination object.
   * @param int $total_count
   *   The total count.
   * @param int $result_count
   *   The result count.
   *
   * @return \Drupal\jsonapi\JsonApiResource\LinkCollection
   *   The link collection.
   */
  protected function getPagerLinks(Request $request, OffsetPage $pagination, int $total_count, int $result_count): LinkCollection {
    $pager_links = new LinkCollection([]);
    $size = (int) $pagination->getSize();
    $offset = $pagination->getOffset();
    $query = (array) $request->query->getIterator();

    // Check if this is not the last page.
    if (($pagination->getOffset() + $result_count) < $total_count) {
      $next_url = static::getRequestLink($request, static::getPagerQueries('next', $offset, $size, $query));
      $pager_links = $pager_links->withLink('next', new Link(new CacheableMetadata(), $next_url, 'next'));
      $last_url = static::getRequestLink($request, static::getPagerQueries('last', $offset, $size, $query, $total_count));
      $pager_links = $pager_links->withLink('last', new Link(new CacheableMetadata(), $last_url, 'last'));
    }
    // Check if this is not the first page.
    if ($offset > 0) {
      $first_url = static::getRequestLink($request, static::getPagerQueries('first', $offset, $size, $query));
      $pager_links = $pager_links->withLink('first', new Link(new CacheableMetadata(), $first_url, 'first'));
      $prev_url = static::getRequestLink($request, static::getPagerQueries('prev', $offset, $size, $query));
      $pager_links = $pager_links->withLink('prev', new Link(new CacheableMetadata(), $prev_url, 'prev'));
    }
    return $pager_links;
  }

  /**
   * Get the query param array.
   *
   * @param string $link_id
   *   The name of the pagination link requested.
   * @param int $offset
   *   The starting index.
   * @param int $size
   *   The pagination page size.
   * @param array $query
   *   The query parameters.
   * @param int $total
   *   The total size of the collection.
   *
   * @return array
   *   The pagination query param array.
   */
  protected static function getPagerQueries($link_id, $offset, $size, array $query = [], $total = 0) {
    $extra_query = [];
    switch ($link_id) {
      case 'next':
        $extra_query = [
          'page' => [
            'offset' => $offset + $size,
            'limit' => $size,
          ],
        ];
        break;

      case 'first':
        $extra_query = [
          'page' => [
            'offset' => 0,
            'limit' => $size,
          ],
        ];
        break;

      case 'last':
        if ($total) {
          $extra_query = [
            'page' => [
              'offset' => (ceil($total / $size) - 1) * $size,
              'limit' => $size,
            ],
          ];
        }
        break;

      case 'prev':
        $extra_query = [
          'page' => [
            'offset' => max($offset - $size, 0),
            'limit' => $size,
          ],
        ];
        break;
    }
    return array_merge($query, $extra_query);
  }

  /**
   * Get the full URL for a given request object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param array|null $query
   *   The query parameters to use. Leave it empty to get the query from the
   *   request object.
   *
   * @return \Drupal\Core\Url
   *   The full URL.
   */
  public static function getRequestLink(Request $request, $query = NULL) {
    if ($query === NULL) {
      return Url::fromUri($request->getUri());
    }

    $uri_without_query_string = $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo();
    return Url::fromUri($uri_without_query_string)->setOption('query', $query);
  }

}
