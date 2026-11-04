# Detailed result handling

* [`Results` class](#results-class)
* [`Record` class](#record-class)
  * [How fields are made available to you](#how-fields-are-made-available-to-you)
  * [Pagination](#pagination)
  * [Adding analytics](#adding-analytics)
    * [Signing analytics links](#signing-analytics-links)

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

The `Results` class is also a `ModelData` object, so these methods can be access in your template with `$isSuccess`,
`$Records`, and `$Facets`.

## `Record` class

The `Record` class is a simple `ModelData` class, and the purpose of it is to provide you with access to the fields
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
<a href="{$getDecoratedLink($Link.forTemplate)}">$Title</a>
```

**Note:** Analytics is perhaps the most likely feature to be missing from search service providers. EG: It is
available through Elastic App Search, but not currently available through Elasticsearch. The goal of this module would
be that if you switched to a new service that did not support analytics, then that portion of your search would be
silently excluded - allowing the core functionality of search to remain functioning without any code changes. **At
some point**, it would be great if Analytics became a CMS feature, rather than us relying on our service providers.

#### Signing analytics links

Without signing, the `_searchAnalytics` param is plain base64 encoded JSON, so anyone can construct or reuse a link and
have a click recorded. To make sure clicks are only recorded for links that your site generated, set a signing secret:

* `SEARCH_ANALYTICS_SECRET="<long random string>"`

You can generate a secret with:

```shell
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

When a secret is set, each decorated link also carries an HMAC-SHA256 signature of its analytics data. Clicks are not
recorded when the signature is missing or doesn't match (the page itself is always rendered as normal). This means:

* Forged links (made-up engine, request or document IDs) aren't recorded.
* Links rendered by another site, or before you set the secret, aren't recorded. That includes old tracked URLs that
  search engines have already indexed.

The signature depends only on the link's data, so the same result always gets the same link. Results pages render
identically on every request, and caching them (EG: by a CDN, static publishing or partial caching) is unaffected.

Signing doesn't stop a genuine link from being followed again later. To keep crawlers away from tracked links, see
the `robots.txt`, `X-Robots-Tag` and `rel="nofollow"` recommendations.

Changing the secret makes every previously rendered link invalid, so clicks on cached results pages aren't recorded
until those pages are rendered again.
