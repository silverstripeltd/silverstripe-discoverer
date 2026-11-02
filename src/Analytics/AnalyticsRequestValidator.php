<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Analytics;

use Psr\Log\LoggerInterface;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;

/**
 * Decides whether a request carrying search analytics data should be recorded as a click.
 *
 * A click is only recorded when it looks like a person in a real browser followed a search result link on this site.
 * A "real browser" is identified by two signals:
 *
 * 1. Fetch Metadata headers. Modern browsers add Sec-Fetch-Site and Sec-Fetch-Mode to every request themselves, and
 *    page scripts cannot set or change them. Clicking a result link on this site produces
 *    "Sec-Fetch-Site: same-origin" and "Sec-Fetch-Mode: navigate". Links from other sites, typed or bookmarked URLs,
 *    and background fetch/prefetch requests produce different values and are rejected. Crawlers and HTTP libraries
 *    usually send no Fetch Metadata at all. Older browsers do not send these headers either, so a request without them
 *    is accepted, unless require_referer_without_fetch_metadata is enabled, in which case it must carry a Referer
 *    from this site.
 * 2. The user agent. An empty user agent, or one that matches a known crawler, bot, monitoring tool or HTTP library
 *    (see CrawlerDetector), is rejected.
 *
 * On top of that, only GET requests (following a link) with a successful (2xx) response are recorded.
 *
 * Checks run in this order:
 *
 * 1. Request checks: HTTP method, crawler user agent, same-origin navigation
 * 2. Response check: the page rendered successfully
 * 3. The updateShouldProcess() extension hook, which can veto or allow the click
 *
 * Every check can be switched off through configuration. Rejections are logged at debug level with one of the
 * REASON_* constants.
 */
class AnalyticsRequestValidator
{

    use Configurable;
    use Extensible;
    use Injectable;

    public const string REASON_METHOD = 'method_not_allowed';

    public const string REASON_CRAWLER = 'crawler_user_agent';

    public const string REASON_CROSS_ORIGIN = 'not_same_origin_navigation';

    public const string REASON_UNSUCCESSFUL_RESPONSE = 'unsuccessful_response';

    public const string REASON_EXTENSION = 'rejected_by_extension';

    public const string FETCH_SITE_SAME_ORIGIN = 'same-origin';

    public const string FETCH_MODE_NAVIGATE = 'navigate';

    /**
     * HTTP methods that may record a click. Following a link is always a GET request.
     *
     * @config
     */
    private static array $allowed_methods = [
        'GET',
    ];

    /**
     * Only record clicks when the response status is 2xx
     *
     * @config
     */
    private static bool $require_successful_response = true;

    /**
     * Reject requests with an empty or known crawler user agent
     *
     * @config
     */
    private static bool $ignore_crawlers = true;

    /**
     * When the browser sends Fetch Metadata headers, require a same-origin top-level navigation
     *
     * @config
     */
    private static bool $require_same_origin = true;

    /**
     * When the browser sends no Fetch Metadata headers, require a Referer from this site. Off by default, as some
     * privacy settings strip the Referer header from genuine clicks.
     *
     * @config
     */
    private static bool $require_referer_without_fetch_metadata = false;

    private static array $dependencies = [
        'logger' => '%$' . LoggerInterface::class,
        'crawlerDetector' => '%$' . CrawlerDetector::class,
    ];

    private ?LoggerInterface $logger = null;

    private ?CrawlerDetector $crawlerDetector = null;

    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setCrawlerDetector(?CrawlerDetector $crawlerDetector): void
    {
        $this->crawlerDetector = $crawlerDetector;
    }

    /**
     * Determine whether this click should be sent to the search service
     */
    public function shouldProcess(HTTPRequest $request, HTTPResponse $response, AnalyticsData $data): bool
    {
        $reason = $this->getRequestRejectionReason($request) ?? $this->getResponseRejectionReason($response);
        $result = $reason === null;

        $this->extend('updateShouldProcess', $result, $request, $response, $data);

        if (!$result) {
            $this->logRejection($reason ?? self::REASON_EXTENSION, $request);

            return false;
        }

        return true;
    }

    /**
     * Checks that only depend on the request, so they can be evaluated before the page is rendered.
     *
     * @return string|null One of the REASON_* constants, or null if the request is acceptable
     */
    public function getRequestRejectionReason(HTTPRequest $request): ?string
    {
        if (!$this->isAllowedMethod($request)) {
            return self::REASON_METHOD;
        }

        if ($this->config()->get('ignore_crawlers') && $this->isCrawler($request)) {
            return self::REASON_CRAWLER;
        }

        if ($this->config()->get('require_same_origin') && !$this->isSameOriginNavigation($request)) {
            return self::REASON_CROSS_ORIGIN;
        }

        return null;
    }

    private function getResponseRejectionReason(HTTPResponse $response): ?string
    {
        if (!$this->config()->get('require_successful_response')) {
            return null;
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode < 200 || $statusCode >= 300) {
            return self::REASON_UNSUCCESSFUL_RESPONSE;
        }

        return null;
    }

    private function isAllowedMethod(HTTPRequest $request): bool
    {
        $allowedMethods = array_map('strtoupper', (array) $this->config()->get('allowed_methods'));

        return in_array(strtoupper($request->httpMethod()), $allowedMethods, true);
    }

    private function isCrawler(HTTPRequest $request): bool
    {
        $crawlerDetector = $this->crawlerDetector ?? CrawlerDetector::create();

        return $crawlerDetector->isCrawler($request->getHeader('User-Agent'));
    }

    /**
     * Fetch Metadata headers are set by the browser and cannot be set by page scripts, so they reliably identify a
     * user following a link on this site. Browsers that do not send them fall back to the optional Referer check.
     */
    private function isSameOriginNavigation(HTTPRequest $request): bool
    {
        $fetchSite = $request->getHeader('Sec-Fetch-Site');

        if ($fetchSite) {
            $fetchMode = (string) $request->getHeader('Sec-Fetch-Mode');

            return strtolower($fetchSite) === self::FETCH_SITE_SAME_ORIGIN
                && strtolower($fetchMode) === self::FETCH_MODE_NAVIGATE;
        }

        if (!$this->config()->get('require_referer_without_fetch_metadata')) {
            return true;
        }

        return $this->isSiteReferer((string) $request->getHeader('Referer'));
    }

    /**
     * The base URL must be followed by a path or query boundary, so that a host which merely starts with this site's
     * host (e.g. www.example.com.other.org) is not accepted.
     */
    private function isSiteReferer(string $referer): bool
    {
        if ($referer === '') {
            return false;
        }

        $baseUrl = rtrim(Director::absoluteBaseURL(), '/');

        return $referer === $baseUrl
            || str_starts_with($referer, $baseUrl . '/')
            || str_starts_with($referer, $baseUrl . '?');
    }

    private function logRejection(string $reason, HTTPRequest $request): void
    {
        $this->logger?->debug(
            'Search analytics click not recorded',
            [
                'reason' => $reason,
                'url' => $request->getURL(),
            ]
        );
    }

}
