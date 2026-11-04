<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Analytics;

use Psr\Log\LoggerInterface;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Core\Environment;
use SilverStripe\Discoverer\Service\SearchService;
use Throwable;

/**
 * Records a search result click and redirects the visitor to the result.
 *
 * When enabled, result links point here instead of carrying analytics data to the result itself. That records clicks
 * on results that never reach this site's PHP code (files served by the web server, and links to other sites), and
 * keeps tracked URLs off content pages.
 *
 * The destination URL and analytics data are signed together with SEARCH_ANALYTICS_SECRET:
 *
 * - Valid signature: the click is recorded (on GET requests) and the visitor is redirected to the destination.
 * - Missing or invalid signature: nothing is recorded. The visitor is only redirected to the destination when it is
 *   on this site; otherwise they're sent to the home page, so the route can't be used as an open redirect.
 *
 * The visitor is always redirected, even if recording fails.
 */
class ClickController extends Controller
{

    public const string URL_SEGMENT = '_search-click';

    private const array ALLOWED_SCHEMES = [
        'http',
        'https',
    ];

    /**
     * Route result links through this controller. Only takes effect when SEARCH_ANALYTICS_SECRET is set; without it,
     * result links keep the _searchAnalytics param.
     *
     * @config
     */
    private static bool $enabled = false;

    private static array $url_handlers = [
        '' => 'index',
    ];

    private static array $allowed_actions = [
        'index',
    ];

    private static array $dependencies = [
        'logger' => '%$' . LoggerInterface::class . '.errorhandler',
    ];

    private ?LoggerInterface $logger = null;

    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Whether result links should point to this controller
     */
    public static function isActive(): bool
    {
        return static::config()->get('enabled') && AnalyticsSigner::singleton()->isEnabled();
    }

    /**
     * Relative URL of this controller for a result link
     */
    public static function linkFor(string $destination, AnalyticsData $analyticsData): string
    {
        return sprintf(
            '%s?%s',
            Controller::join_links(Director::baseURL(), self::URL_SEGMENT),
            $analyticsData->forClickUrl($destination)
        );
    }

    public function index(HTTPRequest $request): HTTPResponse
    {
        $destination = $request->getVar(AnalyticsData::KEY_DESTINATION);
        $destination = is_string($destination) ? trim($destination) : '';
        $analyticsData = AnalyticsData::decode($request->getVar(AnalyticsData::QUERY_PARAM));
        $isTrusted = $analyticsData && $this->isSignatureValid($analyticsData, $destination);

        if ($isTrusted && $request->isGET()) {
            $this->recordClick($request, $analyticsData);
        }

        return $this->createRedirect($this->resolveDestination($destination, $isTrusted));
    }

    private function isSignatureValid(AnalyticsData $analyticsData, string $destination): bool
    {
        $signer = AnalyticsSigner::singleton();
        $signature = $analyticsData->getSignature();

        if ($destination === '' || $signature === null || !$signer->isEnabled()) {
            return false;
        }

        return $signer->verify($analyticsData->getDestinationPayload($destination), $signature);
    }

    /**
     * Only http(s) and relative URLs are ever redirected to. A signed destination is trusted; an unsigned one only if
     * it is on this site.
     */
    private function resolveDestination(string $destination, bool $isTrusted): string
    {
        $homePage = Director::absoluteBaseURL();

        if ($destination === '' || !$this->hasAllowedScheme($destination)) {
            return $homePage;
        }

        if ($isTrusted || Director::is_site_url($destination)) {
            return Director::absoluteURL($destination) ?: $homePage;
        }

        return $homePage;
    }

    private function hasAllowedScheme(string $destination): bool
    {
        $scheme = parse_url($destination, PHP_URL_SCHEME);

        if ($scheme === false) {
            return false;
        }

        return $scheme === null || in_array(strtolower($scheme), self::ALLOWED_SCHEMES, true);
    }

    /**
     * Recording never stops the redirect: failures are logged, and the extension hook can skip recording
     */
    private function recordClick(HTTPRequest $request, AnalyticsData $analyticsData): void
    {
        if (!Environment::getEnv(AnalyticsMiddleware::ENV_ANALYTICS_ENABLED)) {
            return;
        }

        try {
            $shouldRecord = true;
            $this->extend('updateShouldRecordClick', $shouldRecord, $request, $analyticsData);

            if (!$shouldRecord) {
                return;
            }

            SearchService::create()->processAnalytics($analyticsData);
        } catch (Throwable $e) {
            $this->logError($e);
        }
    }

    /**
     * A temporary, uncacheable redirect, so that every click reaches this controller. Search engines are told not to
     * index or follow it.
     */
    private function createRedirect(string $destination): HTTPResponse
    {
        HTTPCacheControlMiddleware::singleton()->disableCache(true);

        $response = HTTPResponse::create()->redirect($destination, 302);
        $response->addHeader('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }

    /**
     * Logging is guarded as well, because a logger can throw, and that must not stop the redirect
     */
    private function logError(Throwable $error): void
    {
        try {
            $this->logger?->error(
                sprintf('Search click not recorded: %s', $error->getMessage()),
                ['exception' => $error]
            );
        } catch (Throwable) {
            return;
        }
    }

}
