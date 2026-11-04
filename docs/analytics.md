# Search analytics (click tracking)

<!-- TOC -->
* [How click tracking works](#how-click-tracking-works)
* [Risks](#risks)
* [Recommended setup checklist](#recommended-setup-checklist)
* [Rate limiting](#rate-limiting)
* [Kill switch](#kill-switch)
<!-- TOC -->

## How click tracking works

1. When `SEARCH_ANALYTICS_ENABLED` is set, your service integration module attaches `AnalyticsData` to each `Record`
   in your results (index name, query string, document ID and the search service's request ID).
2. `$getDecoratedLink($Link)` appends that data to the result link as a `_searchAnalytics` query param (base64 encoded
   JSON).
3. `AnalyticsMiddleware` is registered globally on `Director`, but only when `SEARCH_ANALYTICS_ENABLED` is set (see
   `_config/analytics.yml`). When **any** request to your site contains `_searchAnalytics`, the middleware decodes it
   and calls `SearchService::processAnalytics()`.
4. Your service adaptor (e.g. `silverstripe-discoverer-bifrost`) sends a click event to the search service from your
   server.

> [!IMPORTANT]
> **Every request that carries a decodable `_searchAnalytics` param is sent to your search service as a click event**,
> whether a person or a bot made it, and regardless of the request method or the response status. Click events from
> bots inflate your click analytics and make them unreliable.

## Risks

Tracked links are ordinary URLs on your public site, so anything that can request a URL can generate click events:

* **Crawlers follow and recrawl tracked links.** Search engines, SEO tools, link checkers and monitoring services follow
  every link on a crawlable search results page. Each results page yields a set of unique tracked URLs, and once
  indexed those URLs are recrawled indefinitely.
* **Crawl paths multiply.** Locale switchers, facet links and pagination on a results page each create new results
  pages, and each of those yields another set of tracked URLs.
* **Payloads can be constructed or replayed.** The param is plain base64 JSON. Anyone can build one, or reuse one they
  have seen, and generate click events at will.
* **External links can't be recorded.** A result that links to another domain never passes through your middleware,
  so the param does nothing there except send the search query to that site.

**Warning sign:** in a healthy setup, recorded clicks per search sit around 1 or below. A ratio far above 1 (e.g. 5 or
10 clicks per search) means non-human traffic is generating click events.

## Recommended setup checklist

1. **Keep crawlers off search results and tracked URLs** in `robots.txt`:

   ```
   User-agent: *
   Disallow: /<your-search-page-path>/
   Disallow: /*?_searchAnalytics=
   Disallow: /*&_searchAnalytics=
   ```

   `robots.txt` stops future crawling by well-behaved crawlers, but it doesn't remove URLs that are already indexed.
   Use the search engines' URL removal tools to clear those.

2. **Send `X-Robots-Tag` from your search page controller.** This also covers form actions and AJAX responses, which
   a `<meta name="robots">` tag in a template does not:

   ```php
   protected function init(): void
   {
       parent::init();

       $this->getResponse()->addHeader('X-Robots-Tag', 'noindex, nofollow');
   }
   ```

3. **Add `rel="nofollow"` to links on results pages**: result links, and also locale switchers, facet links and
   pagination.

   ```silverstripe
   <a href="{$getDecoratedLink($Link.forTemplate)}" rel="nofollow">$Title</a>

   <% if $Records.NotLastPage %>
       <a href="$Records.NextLink" rel="nofollow">Next</a>
   <% end_if %>
   ```

4. **Rate limit tracked requests at the infrastructure level.** See [Rate limiting](#rate-limiting).

5. **Only enable `SEARCH_ANALYTICS_ENABLED` if you use the click data.** If nobody looks at click analytics, leave it
   off.

## Rate limiting

Rate limiting and bot management are best handled before requests reach your application, at your CDN, WAF or hosting
layer. We recommend configuring a rule that applies to requests whose query string contains `_searchAnalytics`, for
example:

* rate limit those requests per client (IP address, or IP address and user agent)
* challenge or block known bots and automated clients on those requests
* optionally strip the `_searchAnalytics` param (or block the request) for clients that exceed the limit, so that the
  page is still served without generating a click event

If you host on Silverstripe Cloud, rate limiting and bot rules are not available through `platform.yml`. Its
[web server configuration](https://servicedesk.silverstripe.cloud/support/solutions/articles/75000013059-web-server-configuration)
only covers HTTP settings, Apache tuning and URL rules that route or deny paths. Ask the Silverstripe Cloud Support team
about adding these rules to your
[Web Application Firewall (WAF)](https://servicedesk.silverstripe.cloud/support/solutions/articles/75000012653-web-application-firewall-waf-).

## Kill switch

Unset `SEARCH_ANALYTICS_ENABLED` (and flush). The middleware is only registered when that variable is set, so click
events stop immediately.
