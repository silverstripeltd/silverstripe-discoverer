# Detailed result handling

* [`Results` class](#results-class)
* [`Record` class](#record-class)
  * [How fields are made available to you](#how-fields-are-made-available-to-you)
  * [Pagination](#pagination)
  * [Adding analytics](#adding-analytics)
    * [Which requests are recorded](#which-requests-are-recorded)

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

#### Which requests are recorded

A request carrying `_searchAnalytics` is only recorded as a click when it looks like a person in a browser followed a
search result link on your site. The page is always rendered as normal; only the click event is skipped.

A click is recorded when all of the following are true:

* It is a `GET` request.
* The response status is 2xx (redirects, 404 and 500 pages are not recorded).
* The user agent is not empty and is not a known crawler, bot, monitoring tool or HTTP library.
* The browser's Fetch Metadata headers show a same-origin link navigation (`Sec-Fetch-Site: same-origin` and
  `Sec-Fetch-Mode: navigate`).

Browsers set the Fetch Metadata headers themselves, and page scripts cannot change them. Requests from other sites,
typed or bookmarked URLs and background fetches send different values and are not recorded. Older browsers do not send
these headers at all, so those requests are recorded unless you enable `require_referer_without_fetch_metadata`, which
then requires a `Referer` from your site.

You can also require proof that a person triggered the navigation. Chromium-based browsers and Firefox send
`Sec-Fetch-User: ?1` only when a navigation comes from a click, middle-click or key press, so a navigation without it
was started by a script or an automated browser. Safari and browsers on iOS don't send this header, so it is only
required from user agents that do. It is off by default:

```yaml
SilverStripe\Discoverer\Analytics\AnalyticsRequestValidator:
  require_user_activation: true
```

Crawler detection uses [`jaybizzle/crawler-detect`](https://github.com/JayBizzle/Crawler-Detect) when it is installed,
and a small built-in pattern otherwise. Installing the library is recommended:

```shell
composer require jaybizzle/crawler-detect
```

Each check can be configured or switched off. These are the defaults:

```yaml
SilverStripe\Discoverer\Analytics\AnalyticsRequestValidator:
  allowed_methods:
    - GET
  require_successful_response: true
  ignore_crawlers: true
  require_same_origin: true
  require_referer_without_fetch_metadata: false
  require_user_activation: false
  # User agents expected to send Sec-Fetch-User, and exclusions (iOS browsers and Android WebViews don't send it)
  user_activation_user_agent_pattern: '/\b(Chrome|Chromium|Firefox)\//i'
  user_activation_excluded_user_agent_pattern: '/CriOS|FxiOS|EdgiOS|; wv\)/i'

SilverStripe\Discoverer\Analytics\CrawlerDetector:
  # Set to false to always use user_agent_pattern, even when jaybizzle/crawler-detect is installed
  use_crawler_detect_library: true
```

Rejected clicks are logged at debug level with the reason. You can add your own rules with the `updateShouldProcess()`
extension hook:

```php
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Extension;
use SilverStripe\Discoverer\Analytics\AnalyticsData;

class AnalyticsRequestValidatorExtension extends Extension
{

    protected function updateShouldProcess(
        bool &$result,
        HTTPRequest $request,
        HTTPResponse $response,
        AnalyticsData $data
    ): void {
        // Don't record clicks from your own office network
        if (str_starts_with((string) $request->getIP(), '10.')) {
            $result = false;
        }
    }

}
```

```yaml
SilverStripe\Discoverer\Analytics\AnalyticsRequestValidator:
  extensions:
    - App\Extension\AnalyticsRequestValidatorExtension
```

Per-client rate limiting is not handled by this module. Use your CDN, WAF or web server for that.
