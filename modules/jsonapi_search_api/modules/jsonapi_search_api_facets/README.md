# JSON:API Search API Facets

This module allows for inclusion of facets within the JSON:API Search API
response's meta.

## Requirements

[Search API](https://www.drupal.org/project/search_api) >= 1.15
[Facets](https://www.drupal.org/project/facets)

## Configuration

Configure the facet source for the index to use the `JSON:API Query string` URL
processor at `admin/config/search/facets/facet-sources/jsonapi_search_api_facets:{index}/edit`.

Create facets as per the Facets module [configuration](https://git.drupalcode.org/project/facets/-/blob/8.x-1.x/README.txt)
using the `JSON:API Search API Facets` source plugin for whichever index you
want to configure facets for. Any facet created needs to use the `JSON:API Search API`
widget for results to be properly added to the output.

## Limitations

As of current hierarchical facets, minimum counts and hard limits are not
supported due to how the Facets module has exposed configuration for these
settings. When this issue is resolved these settings will be respected when
configured.
