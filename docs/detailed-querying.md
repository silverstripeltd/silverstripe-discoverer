# Detailed querying

* [`SearchService` class](#searchservice-class)
* [`Query` class](#query-class)
  * [Query string](#query-string)
  * [Search fields](#search-fields)
  * [Result fields](#result-fields)
  * [Sort](#sort)
  * [Pagination](#pagination)
  * [Filters](#filters)
  * [Facets](#facets)

## `SearchService` class

`SearchService` is going to be your main entry point for this module. Currently it provides two methods that you can
use to interact with your chosen service:

* `search`
  * Provide a search query, filters, facets, sorting, etc
* `processAnalytics`
  * Each service is different, but many provide an option to track which result was clicked for any particular search

## `Query` class

All searches start with the `Query` class. It provides you with the ability to search by a query string, filter, sort,
etc.

* [Query string](#query-string)
* [Search fields](#search-fields)
* [Result fields](#result-fields)
* [Sort](#sort)
* [Pagination](#pagination)
* [Filters](#filters)
* [Facets](#facets)

### Query string

The `Query` class accepts a `string` paramter during instantiation, which it will automatically set as the query string:

```php

use SilverStripe\Discoverer\Query\Query;

$query = Query::create('search query');
```

Or it can be set separately, after instantiation:

```php
use SilverStripe\Discoverer\Query\Query;

$query = Query::create();
$query->setQueryString('search query');
```

### Search fields

Limit which fields in your index are searched with your provided query string. For example:

```php
use SilverStripe\Discoverer\Query\Query;

$query = Query::create();
$query->setQueryString('search query');
// Set the `title` as a search field with no weight applied
$query->addSearchField('title');
// Set the `description` as a search field with a weight of 2 applied
$query->addSearchField('description', 2);
// Just for completeness, set the `summary` as a search field with a weight of 0 applied. This has the same result as
// not specifying a weight (like the `title` example)
$query->addSearchField('summary', 0);
```

An array method is also available, and it expects to receive key/value pairs of `$fieldName => $weight`:

```php
use SilverStripe\Discoverer\Query\Query;

$query = Query::create();
// Note that you also need to explicitly specify weights of 0 for fields that you don't want to apply weighting to
$query->addSearchFields([
    'title' => 0,
    'description' => 2,
    'summary' => 0,
]);
```

**Note:** You can only specify each unique field once. If you call this method with the same field multiple times, then
the weight will be overridden with each new method call.

### Result fields

Limit what fields are returned to you as part of your search results. This is also where you can request limits for
length, and request "formatted" result fields (aka "snippets" for some services).

```php
use SilverStripe\Discoverer\Query\Query;

$query = Query::create();
// The "raw" unshortened result for the title field
$query->addResultField('title');
// The formatted, and shortened result for the description field
$query->addResultField('description', 100, true)
```

**Note:** You can specify the same field multiple times. For example, if you would like the `title` field to be returned
with both the formatted and unformatted result.

```php
use SilverStripe\Discoverer\Query\Query;

$query = Query::create();
// The "raw" unshortened result for the title field
$query->addResultField('title');
// The formatted, and shortened result for the title field
$query->addResultField('title', 50, true)
```

**Note:** What the "length" here described depends on which service you are accessing. EG: For Elastic it is characters,
but for Algolia it is words. This module does make any determinations.

### Sort

```php
use SilverStripe\Discoverer\Query\Query;

$query = Query::create();
// Default is ASC
$query->addSort('title');
// You can specify multiple sort fields
$query->addSort('last_edited', Query::SORT_DESC);
```

An array method is also available, and it expects to receive key/value pairs of `$fieldName => $direction`:

```php
use SilverStripe\Discoverer\Query\Query;

$query = Query::create();
// Note that you also need to explicitly specify ASC (even though it is the default when using addSort())
$query->addSorts([
    'title' => Query::SORT_ASC,
    'last_edited' => Query::SORT_DESC,
]);
```

### Pagination

```php
use SilverStripe\Discoverer\Query\Query;

$query = Query::create();
// Can specify both page size (10) and offset (20) in one call
$query->setPagination(10, 20)
// Or specify them separately
$query->setPaginationLimit(10);
$query->setPaginationOffset(20);
```

**Note:** Some services (EG: Elastic) use page numbers instead of offset, but since our `PaginatedList` uses an offset
when it creates its pagination links, it makes sense for us to similarly use offset, in order to keep things simple.

**Note:** Offset starts at 0, so for the example above, if you have 10 records per page, and an offset of 20, then that
is actually page 3.

### Filters

Filtering is a complicated beast, so I hope you're ready.

For basic filtering, there are some methods available to try and keep things as simple as possible.

#### `filter()` method

The default `filter()` method accepts a single filter condition. Calling the `filter()` method multiple times will
result in filter conditions that use the AND conjunction between them. "This AND that AND those".

```php
use SilverStripe\Discoverer\Query\Filter\Criterion;use SilverStripe\Discoverer\Query\Query;

$query = Query::create();
// Where the author_id is specifically 1
$query->filter('author_id', 1, Criterion::EQUAL);
// Where the category_id is any of 1, 2, or 3
$query->filter('category_id', [1, 2, 3], Criterion::IN);
```

The example above would result in a filter condition that reads like:

```
(
    author_id = 1
    AND
    category_id IN (1, 2, 3)
)
```

The `filter()` method also accepts a `Criteria` or `Criterion` as the first argument. You can use this if you have
pre-build the filter conditions that you wish to apply to your `Query`.

See [Complex (EG: nested) filtering](#complex-eg-nested-filtering) for more info on that though.

#### `filterAny()` method

Each time you call `filterAny()`, you can provide an array of filtering conditions, and each condition will use the OR
conjuntion between them.

```php
use SilverStripe\Discoverer\Query\Filter\Criterion;use SilverStripe\Discoverer\Query\Query;

$query = Query::create();
$query->filterAny([
    ['author_id', 1, Criterion::EQUAL],
    ['publisher_id', [1, 2], Criterion::IN],
]);
```

The example above would result in a filter condition that reads like:

```
(
    author_id = 1
    OR
    publisher_id IN (1, 2)
)
```

*Note:* Calling `filterAny()` multiple times will result in **groups** of filter conditions, and each groups of
conditions will use the AND conjunction between them.

```php
use SilverStripe\Discoverer\Query\Filter\Criterion;use SilverStripe\Discoverer\Query\Query;

$query = Query::create();
$query->filterAny([
    ['author_id', 1, Criterion::EQUAL],
    ['publisher_id', [1, 2], Criterion::IN],
]);
$query->filterAny([
    ['category_id', 1, Criterion::EQUAL],
    ['subcategory_id', [1, 2], Criterion::IN],
]);
```

The example above would result in a filter condition that reads like:

```
(
    (
        author_id = 1
        OR
        publisher_id IN (1, 2)
    )
    AND
    (
        category_id = 1
        OR
        subcategory_id IN (1, 2)
    )
)
```

#### Using both `filter()` and `filterAny()`

Absolutely you can do this. The main thing to remember is that each call to `filter()` or `filterAny()` uses an AND
conjunction between them.

```php
use SilverStripe\Discoverer\Query\Filter\Criterion;use SilverStripe\Discoverer\Query\Query;

$query = Query::create();
$query->filter('author_id', 1, Criterion::EQUAL);
$query->filter('publisher_id', [1, 2, 3], Criterion::IN);
$query->filterAny([
    ['category_id', 1, Criterion::EQUAL],
    ['subcategory_id', [1, 2], Criterion::IN],
]);
```

The example above would result in a filter condition that reads like:

```
(
    author_id = 1
    AND
    publisher_id IN (1, 2, 3)
    AND
    (
        category_id = 1 OR subcategory_id IN (1, 2)
    )
)
```

#### Complex (EG: nested) filtering

In order to provide a "service agnostic" filtering service, we have to have some classes to build our filters around, so
that we can then use those classes in different "plugin" modules to format them correctly for those different sevices.

For this, we have two main classes (the names of which I took from from popular ORMs):

* `Criterion`: A **single** comparison. EG:
  * `category_id=1` is a single comparison
  * `category_id IN (1, 2, 3)` is another single comparison
  * `category_id NOT_IN (4, 5, 6)` is another single comparison
  * etc
* `Criteria`: A **collection** of `Criterion` and other `Criteria`.
  * This is the main way that we can start to build conjunctions of comparisons. EG:
    * `(category_id=1 OR sub_category=2)`
    * `(author_id=1 OR publisher_id=2)`
    * Or both together: `(category_id=1 OR sub_category=2) AND (author_id=1 OR publisher_id=2)`
  * The level of nesting we can achieve here is limited only by the service you are using. EG: Elastic has a nesting
    limit of 5 levels

Ok, so the key is going to be to remember that the `Criteria` class is for groups of conditions. So let's say you want
to create one group of conditions that use an OR conjunction between them.

```php
use SilverStripe\Discoverer\Query\Filter\Criteria;use SilverStripe\Discoverer\Query\Filter\Criterion;

// Start by creating a `Criteria` that uses the OR conjunction
$criteriaOne = Criteria::createAny();
// Then you can start adding filter conditions
// Note: Because we created the Criteria using createAny(), we will get an OR conjunction between each filter
$criteriaOne->filter('category_id', 1, Criterion::EQUAL);
$criteriaOne->filter('subcategory_id', 2, Criterion::EQUAL);
// This will create a filter condition that reads like:
// (category_id = 1 OR subcategory_id = 2)

// And then maybe we have another set of Criteria, but this time we want to use the AND conjunction
$criteriaTwo = Criteria::createAll();
// Again, start adding filter conditions
$criteriaTwo->filter('author_id', 1, Criterion::EQUAL);
$criteriaTwo->filter('publisher_id', 2, Criterion::EQUAL);
// This will create a filter condition that reads like:
// (author_id = 1 AND publisher_id = 2)

// Ok, so then we want to combine these two groups of conditions together using an OR conjunction. To do that, we
// could create a third Criteria::createAny()
$criteriaJoined = Criteria::createAny();
// And then we could add both of the previous Criteria to it, creating a nested group of conditions
$criteriaJoined->filter($criteriaOne);
$criteriaJoined->filter($criteriaTwo);
// This joined Criteria would now create a filter condition that reads like:
// ((category_id = 1 OR subcategory_id = 2) OR (author_id = 1 AND publisher_id = 2))
```

`$criteriaJoined` could then also be added into another `Criteria` object, creating another level of nesting.

I hope the above gives you an idea of how far you might be able to push filtering. As mentioned before, the level of
nesting is determined by the search service, but (for example), Elastic supports 5 levels, and you could achieve this
by using nested `Criteria` objects.

### Facets

Facets are an area where support differs greatly between search service providers. We've tried to provide a reasonable
level of support here, in the hope that if/when we switch to a different underlying service, we won't lose any
functionality.

We support two basic facet types: Value, and Range.

Value Facets are created by default, and if you add a range, then they are automatically updated to the Range type.

```php
use SilverStripe\Discoverer\Query\Facet\Facet;use SilverStripe\Discoverer\Query\Query;

// Create your Query
$query = Query::create('query string');

// Create a facet (defaults to Value type)
$facet = Facet::create();
// Set the property name (aka "field key")
$facet->setProperty('fieldName1');
// Set the facet name
$facet->setName('facetName1');
// Optionally set a limit
$facet->setLimit(5);

// Create a facet (defaults to Value type)
$facetTwo = Facet::create();
// Set the property name (aka "field key")
$facetTwo->setProperty('fieldName2');
// Set the facet name
$facetTwo->setName('facetName2');
// Convert it to a Range type simply by adding one or more ranges
$facetTwo->addRange(100, 1000);

// Add your facet (or facets) to the Query
$query->addFacet($facet);
$query->addFacet($facetTwo);

// An array method is also available, and it expects an array of facets
$query->addFacets([$facet, $facetTwo]);
```
