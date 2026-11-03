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

By default, every request carrying `_searchAnalytics` is recorded as a click, including requests from crawlers, link
previews and other automated clients. `AnalyticsRequestValidator` can filter these out, so that a click is only
recorded when it looks like a person in a browser followed a search result link on your site. Every check is off by
default; turn on the ones that suit your site. The page is always rendered as normal; only the click event is skipped.

This is the recommended configuration:

```yaml
SilverStripe\Discoverer\Analytics\AnalyticsRequestValidator:
  # Following a link is a GET request
  allowed_methods:
    - GET
  # Skip empty and known crawler, bot, monitoring and HTTP library user agents
  ignore_crawlers: true
  # Only same-origin link navigations that aren't prefetch or prerender (see below)
  require_same_origin: true
  # Only 2xx responses (see the note on redirects below)
  require_successful_response: true
```

Crawler detection uses [`jaybizzle/crawler-detect`](https://github.com/JayBizzle/Crawler-Detect) when it is installed,
and a small built-in pattern otherwise. Installing the library is recommended:

```shell
composer require jaybizzle/crawler-detect
```

##### Same-origin navigation (`require_same_origin`)

Browsers add Fetch Metadata headers to their requests themselves, and page scripts cannot change them. A click on a link
on your site arrives as `Sec-Fetch-Site: same-origin`, `Sec-Fetch-Mode: navigate` and `Sec-Fetch-Dest: document`. With
`require_same_origin` on:

* Requests with other values are not recorded: links from other sites, typed or bookmarked URLs, background fetches
  and iframes.
* Prefetch and prerender requests are not recorded. Speculation rules (including ones added by CDNs) send the same
  Fetch Metadata as a click, so they're identified by their `Sec-Purpose` or `Purpose` header instead. If the visitor
  then opens a prefetched or prerendered result, the browser may use the loaded copy without a new request, and that
  click isn't recorded.
* A request without Fetch Metadata is not recorded when its user agent claims a browser that always sends it
  (Chromium 76+, Firefox 90+, Safari 16.4+), because it comes from a script. Requests from older browsers are
  recorded, unless you also turn on `require_referer_without_fetch_metadata`, which then requires a `Referer` from
  your site. The browser versions are set in `fetch_metadata_browsers`.

This check also rejects clicks between different origins of one install, such as Subsites or Fluent domains, an http
link to an https site, or a search UI hosted on another origin. Leave it off if your site relies on those.

##### User activation (`require_user_activation`)

Chromium-based browsers and Firefox send `Sec-Fetch-User: ?1` only when a navigation comes from a click, middle-click or
key press, so a navigation without it was started by a script or an automated browser. Safari and browsers on iOS don't
send this header, so it is only required from user agents that do:

```yaml
SilverStripe\Discoverer\Analytics\AnalyticsRequestValidator:
  require_user_activation: true
```

##### Redirects (`require_successful_response`)

With `require_successful_response` on, a result that links to a redirect (for example a `RedirectorPage` or a
redirected URL) returns a 3xx and isn't recorded. The page it redirects to doesn't carry `_searchAnalytics`, so that
click isn't recorded at all. Leave this off, or link results to their final URL, if that matters for your site.

##### All options

These are the defaults:

```yaml
SilverStripe\Discoverer\Analytics\AnalyticsRequestValidator:
  allowed_methods: []
  require_successful_response: false
  ignore_crawlers: false
  require_same_origin: false
  require_referer_without_fetch_metadata: false
  require_user_activation: false

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
