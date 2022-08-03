<?php

namespace Drupal\jsonapi_search_api\Event;

/**
 * Contains all events emitted during building the search's meta.
 *
 * @see \Drupal\jsonapi_api_search_api\Event\AddSearchMetaEvent
 */
final class Events {

  /**
   * Emitted during the resource type build process.
   */
  const ADD_SEARCH_META = 'jsonapi_search_api.add_search_meta';

}
