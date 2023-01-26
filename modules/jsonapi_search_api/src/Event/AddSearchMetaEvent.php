<?php

namespace Drupal\jsonapi_search_api\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;

/**
 * Allows for other sources to modify the meta portion of the search.
 */
class AddSearchMetaEvent extends Event {

  /**
   * Meta information to be added to the search.
   *
   * @var array
   */
  protected $meta;

  /**
   * The query that was executed.
   *
   * @var \Drupal\search_api\Query\QueryInterface
   */
  protected $query;

  /**
   * Results returned from the search.
   *
   * @var \Drupal\search_api\Query\ResultSetInterface
   */
  protected $results;

  /**
   * AddSearchMetaEvent constructor.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   Query that was executed.
   * @param \Drupal\search_api\Query\ResultSetInterface $results
   *   Results returned from the search.
   * @param array $meta
   *   An array representing the meta portion of the JSON:API response.
   */
  public function __construct(QueryInterface $query, ResultSetInterface $results, array $meta) {
    $this->query = $query;
    $this->results = $results;
    $this->meta = $meta;
  }

  /**
   * Getter for the query.
   *
   * @return \Drupal\search_api\Query\QueryInterface
   *   The query.
   */
  public function getQuery() {
    return $this->query;
  }

  /**
   * Getter for the results.
   *
   * @return \Drupal\search_api\Query\ResultSetInterface
   *   The results.
   */
  public function getResults() {
    return $this->results;
  }

  /**
   * Getter for the meta.
   *
   * @return array
   *   The meta to be used.
   */
  public function getMeta() {
    return $this->meta;
  }

  /**
   * Sets an entry in the meta.
   *
   * @param string $key
   *   The key to be used in the meta portion of the JSON:API response.
   * @param string|array $value
   *   The value to be used in the JSON:API response.
   */
  public function setMeta($key, $value) {
    $this->meta[$key] = $value;
  }

}
