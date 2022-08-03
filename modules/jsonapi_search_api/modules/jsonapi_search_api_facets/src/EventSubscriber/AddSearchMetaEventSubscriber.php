<?php

namespace Drupal\jsonapi_search_api_facets\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\jsonapi_search_api\Event\AddSearchMetaEvent;
use Drupal\jsonapi_search_api\Event\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to add facets into search results.
 */
class AddSearchMetaEventSubscriber implements EventSubscriberInterface {

  /**
   * The facet manager.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected $facetManager;

  /**
   * The entity storage used for facets.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $facetStorage;

  /**
   * Constructs a new event subscriber.
   *
   * @param Drupal\facets\FacetManager\DefaultFacetManager $facet_manager
   *   The facet manager.
   * @param Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(DefaultFacetManager $facet_manager, EntityTypeManager $entity_type_manager) {
    $this->facetManager = $facet_manager;
    $this->facetStorage = $entity_type_manager->getStorage('facets_facet');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[Events::ADD_SEARCH_META][] = ['appendFacets'];
    return $events;
  }

  /**
   * Adds facet information into the meta of the JSON:API Search API response.
   *
   * @param \Drupal\jsonapi_search_api\Event\AddSearchMetaEvent $event
   *   The event being subscribed to.
   */
  public function appendFacets(AddSearchMetaEvent $event) {
    $query = $event->getQuery();
    if (strpos($query->getSearchId(), 'jsonapi_search_api:') === 0 && $query->getIndex()->getServerInstance()->supportsFeature('search_api_facets')) {
      $facet_source_id = strtr('jsonapi_search_api_facets:!index', [
        '!index' => $query->getIndex()->id(),
      ]);
      $facets = $this->facetManager->getFacetsByFacetSourceId($facet_source_id);
      $meta = [];
      foreach ($facets as $facet) {
        // No need to build the facet if it does not need to be visible.
        if ($facet->getOnlyVisibleWhenFacetSourceIsVisible() && (!$facet->getFacetSource() || !$facet->getFacetSource()->isRenderedInCurrentRequest())) {
          continue;
        }
        // Let the facet_manager build the facets, it returns each facet wrapped
        // in an array for some reason. For better readability get rid of that.
        $facet_results = $this->facetManager->build($facet);
        $facet_data = reset($facet_results);

        // If there are no results, the facet manager adds empty behavior render
        // array data. We need to strip that out.
        if (count($facet->getResults()) === 0) {
          $facet_data = reset($facet_data);
        }

        if ($facet_data) {
          $meta[] = $facet_data;
        }
      }
      if (!empty($meta)) {
        $event->setMeta('facets', $meta);
      }
    }
  }

}
