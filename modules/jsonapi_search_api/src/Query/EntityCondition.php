<?php

namespace Drupal\jsonapi_search_api\Query;

use Drupal\jsonapi\Query\EntityCondition as BaseEntityCondition;

/**
 * A condition object for the EntityQuery.
 */
class EntityCondition extends BaseEntityCondition {

  /**
   * The allowed condition operators.
   *
   * The operators 'STARTS_WITH', 'CONTAINS', 'ENDS_WITH' are not supported by
   * the Search API module.
   *
   * @var string[]
   *
   * @see \Drupal\search_api\Query\ConditionSetInterface
   */
  public static $allowedOperators = [
    '=', '<>',
    '>', '>=', '<', '<=',
    'IN', 'NOT IN',
    'BETWEEN', 'NOT BETWEEN',
    'IS NULL', 'IS NOT NULL',
  ];

}
