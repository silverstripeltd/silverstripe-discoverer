# Detailed result handling

* [`Results` class](#results-class)
* [`Record` class](#record-class)
  * [How fields are made available to you](#how-fields-are-made-available-to-you)
  * [Pagination](#pagination)
  * [Adding analytics](#adding-analytics)

Ok, so you've written some queries and run some searches, and now you want to understand how that information is going
to be made available for use in (say) a Silverstripe template.

This page will use some examples from Elastic, but please keep in mind that this module is designed to be service
agnostic, and different services definitely do things in different ways.

## `Results` class

Whenever you perform a search through the `SearchService`, you will receive a `Results` object. This object has some
simple methods available that you can access anywhere.

* `isSuccess()`: Simply states whether or not the search was a success, or error.
* `getRecords()`: A `PaginatedList` of `Record` objects that were returned by the search service based on your `Query`.
* `getFacets`: An `ArrayList` of `Facet` objects that were returned by the search service based on your `Query`.
* `getQuery`": The `Query` object

The `Results` class is also a `ViewableData` object, so these methods can be access in your template with `$isSuccess`,
`$Records`, `$Facets`, and `$Query`.

## `Query` class
The `Query` class provides the original query that was used for matching results. This is available should you wish
to include `Showing results for "test"` or similar text on your results page.

**Important:** When including `$Query` in the template it should be noted that this assumes the query string is
safe and that the implementation has sanitised the user input to mitigate against cross-site scripting (xss) attacks.

## `Record` class

The `Record` class is a simple `ViewableData` class, and the purpose of it is to provide you with access to the fields
that are returned by your search service, and to make those fields available to you in (EG) Silverstripe templates.

**Note:** A `Record` is not assumed to have a linked `DataObject`. The recommendation would be for you to index all of
the data you need access to as part of your search Documents. That way, you don't have to perform additional look ups
in your database.

You will notice that there are only a couple of pre-defined methods in this class - `getAnalyticsData()`, and
`getDecoratedLink()`. All other fields are dynamic properties - because we have no way of programmatically knowing what
fields you might have in your search index.

### How fields are made available to you

As mentioned above, there is no way for this module to know what fields you have defined for your search index, but what
we can do is provide you with a predictable pattern to follow in order to access those fields.

We ask that plugin modules for services match the requirements outlined in [Field conversions](field-convensions.md).

**TL;DR:** All of your fields should be available through PascalCase, with abbreviations being treated as one word, and
so similarly getting PascalCase applied (EG: ID = Id, IDs = Ids, URL = Url, etc).

Here are some examples for an Elastic App Search index (where App Search uses snake_case for its field names):

* `id`: `$Id`
* `_id` (this is from Elasticsearch, rather than App Search): `$Id`
* `title`: `$Title`
* `elemental_area`: `$ElementalArea`
* `record_id`: `$RecordId`

If you have analytics enabled, then you will also have a field available called `$AnalyticsData`, and a method called
`$getDecoratedLink()`. See [Adding analytics](#adding-analytics).

### Pagination

Your `Records` are provided in a `PaginatedList`, so this can be used like any other `PaginatedList` to create your
pagination.

For an example, see the template code in [Simple usage](simple-usage.md).

### Adding analytics

This module comes with an HTTP middleware that you can apply by setting the following env var:

* `SEARCH_ANALYTICS_ENABLED=1`

If you do this, then you will have access to `$AnalyticsData` and `$getDecoratedLink()` for your `Record` objects.
Eiter of these can be used to add analytics tracking data to your link (though, `$getDecoratedLink()` is the easier of
the two, as it will automatically consider whether there are existing GET params in the link before appending more).

The following example assumes that you have indexed a `title` and `link` for each search Document.

```silverstripe
<a href="{$getDecoratedLink($Link)}">$Title</a>
```

**Note:** Analytics is perhaps the most likely feature to be missing from search service providers. EG: It is
available through Elastic App Search, but not currently available through Elasticsearch. The goal of this module would
be that if you switched to a new service that did not support analytics, then that portion of your search would be
silently excluded - allowing the core functionality of search to remain functioning without any code changes. **At
some point**, it would be great if Analytics became a CMS feature, rather than us relying on our service providers.
