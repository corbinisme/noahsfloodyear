# JSON:API Search API

This module exposes Search API indexes over JSON:API Resources.

## Usage

Each Search API index will be exposed as a JSON:API resource with its own route. The resource path is:

```
/jsonapi/index/{index_id}
```

For example, if you had a Search API index with the machine name `articles` the resource path would be `/jsonapi/index/articles`.

Note, if you changed your `jsonapi.base_path`, the path prefix will reflect that value

### Fulltext searching

To perform a fulltext search, pass the `fulltext` filter query parameter. This is a special filter which sets the fulltext search for the index query.

```
/jsonapi/index/{index_id}?filter[fulltext]=SEARCH_STRING
```

### Filtering index values

The index resource supports querying specific index fields.

```
/jsonapi/index/{index_id}?filter[index_field]=FIELD_VALUE
```
