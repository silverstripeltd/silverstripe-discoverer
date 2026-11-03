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
 * Every check is off by default, so every request carrying analytics data is recorded. Each check can be turned on
 * through configuration:
 *
 * - allowed_methods: only record these HTTP methods. Following a link is a GET request.
 * - ignore_crawlers: reject an empty user agent, or one that matches a known crawler, bot, monitoring tool or HTTP
 *   library (see CrawlerDetector).
 * - require_same_origin: browsers add Fetch Metadata headers (Sec-Fetch-*) to their requests themselves, and page
 *   scripts cannot set or change them. A click on a link on this site arrives as Sec-Fetch-Site: same-origin,
 *   Sec-Fetch-Mode: navigate and Sec-Fetch-Dest: document. Other values (links from other sites, typed or bookmarked
 *   URLs, background fetches, iframes) are rejected, and so are speculative loads (prefetch and prerender, flagged by
 *   the Sec-Purpose or Purpose header), which send the same Fetch Metadata as a click. On HTTPS, a request without
 *   Fetch Metadata is rejected when its user agent claims a browser version that always sends it (see
 *   fetch_metadata_browsers), as it then comes from a script. Browsers only send Fetch Metadata to secure origins, so
 *   this isn't checked over plain http. Otherwise the request is accepted, unless
 *   require_referer_without_fetch_metadata is on.
 * - require_user_activation: browsers that support it send Sec-Fetch-User: ?1 only when a person triggered the
 *   navigation (click, middle-click, keyboard). Not every browser sends it (notably Safari and every browser on
 *   iOS), so it is only required from user agents matched by user_activation_user_agent_pattern.
 * - require_successful_response: only record 2xx responses.
 *
 * Checks run in this order:
 *
 * 1. Request checks: HTTP method, crawler user agent, same-origin navigation, user activation
 * 2. Response check: the response status
 * 3. The updateShouldProcess() extension hook, which can veto or allow the click
 *
 * Rejections are logged at debug level with one of the REASON_* constants.
 */
class AnalyticsRequestValidator
{

    use Configurable;
    use Extensible;
    use Injectable;

    public const string REASON_METHOD = 'method_not_allowed';

    public const string REASON_CRAWLER = 'crawler_user_agent';

    public const string REASON_CROSS_ORIGIN = 'not_same_origin_navigation';

    public const string REASON_SPECULATIVE = 'speculative_load';

    public const string REASON_MISSING_FETCH_METADATA = 'missing_fetch_metadata';

    public const string REASON_NO_USER_ACTIVATION = 'no_user_activation';

    public const string REASON_UNSUCCESSFUL_RESPONSE = 'unsuccessful_response';

    public const string REASON_EXTENSION = 'rejected_by_extension';

    public const string FETCH_SITE_SAME_ORIGIN = 'same-origin';

    public const string FETCH_MODE_NAVIGATE = 'navigate';

    public const string FETCH_DEST_DOCUMENT = 'document';

    public const string FETCH_USER_ACTIVATED = '?1';

    /**
     * Matches the Sec-Purpose (current) and Purpose (legacy) header values sent with speculative loads
     */
    private const string SPECULATIVE_PURPOSE_PATTERN = '/prefetch|prerender/i';

    /**
     * HTTP methods that may record a click. Empty means any method. Following a link is a GET request, so ['GET'] is
     * recommended.
     *
     * @config
     */
    private static array $allowed_methods = [];

    /**
     * Only record clicks when the response status is 2xx. A result link that redirects (for example to a
     * RedirectorPage or a redirected URL) returns a 3xx, and the redirect target doesn't carry the analytics data, so
     * that click is not recorded at all when this is on.
     *
     * @config
     */
    private static bool $require_successful_response = false;

    /**
     * Reject requests with an empty or known crawler user agent
     *
     * @config
     */
    private static bool $ignore_crawlers = false;

    /**
     * Require a same-origin top-level navigation that isn't a speculative load, based on Fetch Metadata headers. Note
     * that this also rejects clicks between origins of one install (Subsites or Fluent domains, an http link to an
     * https site, or a search UI hosted on another origin).
     *
     * @config
     */
    private static bool $require_same_origin = false;

    /**
     * With require_same_origin on: when the browser sends no Fetch Metadata headers, require a Referer from this site.
     * Off by default, as some privacy settings strip the Referer header from genuine clicks.
     *
     * @config
     */
    private static bool $require_referer_without_fetch_metadata = false;

    /**
     * With require_same_origin on: browsers whose user agent proves they send Fetch Metadata on every request to a
     * secure origin, so an HTTPS request claiming one of them without Fetch Metadata comes from a script. Each entry
     * has a pattern whose first capture group is the browser version, and the minimum version that sends Fetch
     * Metadata.
     *
     * @config
     */
    private static array $fetch_metadata_browsers = [
        'chromium' => [
            'pattern' => '/\b(?:Chrome|Chromium)\/(\d+(?:\.\d+)?)/',
            'minimum_version' => '76',
        ],
        'firefox' => [
            'pattern' => '/\bFirefox\/(\d+(?:\.\d+)?)/',
            'minimum_version' => '90',
        ],
        'safari' => [
            'pattern' => '/\bVersion\/(\d+(?:\.\d+)?)[^ ]*(?: Mobile\/\S+)? Safari\//',
            'minimum_version' => '16.4',
        ],
    ];

    /**
     * User agents never treated as a fetch_metadata_browsers match: browsers on iOS other than Safari and Android
     * WebViews (in-app browsers), whose Fetch Metadata support can't be told from the user agent
     *
     * @config
     */
    private static string $fetch_metadata_excluded_user_agent_pattern = '/CriOS|FxiOS|EdgiOS|; wv\)/i';

    /**
     * Require "Sec-Fetch-User: ?1" (a navigation triggered by a person) from browsers that send it
     *
     * @config
     */
    private static bool $require_user_activation = false;

    /**
     * User agents expected to send Sec-Fetch-User on user-activated navigations: Chromium-based browsers and Firefox
     *
     * @config
     */
    private static string $user_activation_user_agent_pattern = '/\b(Chrome|Chromium|Firefox)\//i';

    /**
     * User agents excluded from the user activation check even when they match user_activation_user_agent_pattern:
     * browsers on iOS (which all use WebKit, which does not send Sec-Fetch-User) and Android WebViews (in-app browsers)
     *
     * @config
     */
    private static string $user_activation_excluded_user_agent_pattern = '/CriOS|FxiOS|EdgiOS|; wv\)/i';

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

        if ($this->config()->get('require_same_origin')) {
            $reason = $this->getOriginRejectionReason($request);

            if ($reason !== null) {
                return $reason;
            }
        }

        if ($this->config()->get('require_user_activation') && !$this->hasUserActivation($request)) {
            return self::REASON_NO_USER_ACTIVATION;
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

    private function isCrawler(HTTPRequest $request): bool
    {
        return $this->crawlerDetector?->isCrawler($request->getHeader('User-Agent')) ?? false;
    }

    private function isAllowedMethod(HTTPRequest $request): bool
    {
        $allowedMethods = array_map('strtoupper', (array) $this->config()->get('allowed_methods'));

        if (!$allowedMethods) {
            return true;
        }

        return in_array(strtoupper($request->httpMethod()), $allowedMethods, true);
    }

    /**
     * @return string|null One of the REASON_* constants, or null if the request is a same-origin navigation
     */
    private function getOriginRejectionReason(HTTPRequest $request): ?string
    {
        if ($request->getHeader('Sec-Fetch-Site')) {
            return $this->getFetchMetadataRejectionReason($request);
        }

        // Browsers only send Fetch Metadata to secure origins, so its absence over plain http says nothing
        $userAgent = (string) $request->getHeader('User-Agent');

        if (Director::is_https($request) && $this->isFetchMetadataExpected($userAgent)) {
            return self::REASON_MISSING_FETCH_METADATA;
        }

        if ($this->config()->get('require_referer_without_fetch_metadata')
            && !$this->isSiteReferer((string) $request->getHeader('Referer'))
        ) {
            return self::REASON_CROSS_ORIGIN;
        }

        return null;
    }

    /**
     * Speculative loads are checked as well as the Fetch Metadata values, because prefetch and prerender requests
     * (including ones added by CDNs) send the same Sec-Fetch-Site, Sec-Fetch-Mode and Sec-Fetch-Dest as a click.
     */
    private function getFetchMetadataRejectionReason(HTTPRequest $request): ?string
    {
        $purpose = sprintf('%s %s', $request->getHeader('Sec-Purpose'), $request->getHeader('Purpose'));

        if (preg_match(self::SPECULATIVE_PURPOSE_PATTERN, $purpose) === 1) {
            return self::REASON_SPECULATIVE;
        }

        $isNavigation = strtolower((string) $request->getHeader('Sec-Fetch-Site')) === self::FETCH_SITE_SAME_ORIGIN
            && strtolower((string) $request->getHeader('Sec-Fetch-Mode')) === self::FETCH_MODE_NAVIGATE
            && strtolower((string) $request->getHeader('Sec-Fetch-Dest')) === self::FETCH_DEST_DOCUMENT;

        return $isNavigation ? null : self::REASON_CROSS_ORIGIN;
    }

    private function isFetchMetadataExpected(string $userAgent): bool
    {
        $excludedPattern = (string) $this->config()->get('fetch_metadata_excluded_user_agent_pattern');

        if ($userAgent === '' || ($excludedPattern !== '' && preg_match($excludedPattern, $userAgent) === 1)) {
            return false;
        }

        foreach ((array) $this->config()->get('fetch_metadata_browsers') as $browser) {
            $pattern = (string) ($browser['pattern'] ?? '');
            $minimumVersion = (string) ($browser['minimum_version'] ?? '');

            if ($pattern === '' || $minimumVersion === '' || preg_match($pattern, $userAgent, $matches) !== 1) {
                continue;
            }

            if (version_compare($matches[1] ?? '0', $minimumVersion, '>=')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Requests without Fetch Metadata, and from browsers that never send Sec-Fetch-User, cannot be judged by this
     * header, so they pass.
     */
    private function hasUserActivation(HTTPRequest $request): bool
    {
        if (!$request->getHeader('Sec-Fetch-Site')) {
            return true;
        }

        if (!$this->isUserActivationExpected((string) $request->getHeader('User-Agent'))) {
            return true;
        }

        return $request->getHeader('Sec-Fetch-User') === self::FETCH_USER_ACTIVATED;
    }

    private function isUserActivationExpected(string $userAgent): bool
    {
        $pattern = (string) $this->config()->get('user_activation_user_agent_pattern');
        $excludedPattern = (string) $this->config()->get('user_activation_excluded_user_agent_pattern');

        if ($pattern === '' || preg_match($pattern, $userAgent) !== 1) {
            return false;
        }

        return $excludedPattern === '' || preg_match($excludedPattern, $userAgent) !== 1;
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
