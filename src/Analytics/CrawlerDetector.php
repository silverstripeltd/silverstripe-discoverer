<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Analytics;

use Jaybizzle\CrawlerDetect\CrawlerDetect;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;

/**
 * Classifies a user agent as automated (crawler, bot, monitoring tool, HTTP library) or not.
 *
 * Uses jaybizzle/crawler-detect when it is installed, as it maintains a far more complete list of agents. Otherwise
 * falls back to a small built-in pattern that covers the most common crawlers and HTTP clients.
 */
class CrawlerDetector
{

    use Configurable;
    use Injectable;

    /**
     * Use jaybizzle/crawler-detect (when installed) instead of user_agent_pattern
     *
     * @config
     */
    private static bool $use_crawler_detect_library = true;

    /**
     * Case-insensitive fallback pattern used when jaybizzle/crawler-detect is unavailable or disabled
     *
     * @config
     */
    private static string $user_agent_pattern = '/bot\b|bot\/|crawl|spider|slurp|archiver|facebookexternalhit'
        . '|embedly|preview|monitor|uptime|pingdom|lighthouse|headless|phantomjs|semrush|ahrefs|screaming frog'
        . '|scrapy|curl|wget|libwww|python-requests|python-urllib|httpclient|java\/|go-http-client|okhttp'
        . '|axios|node-fetch|guzzle/i';

    /**
     * An empty user agent is treated as automated, as every mainstream browser sends one.
     */
    public function isCrawler(?string $userAgent): bool
    {
        $userAgent = trim((string) $userAgent);

        if ($userAgent === '') {
            return true;
        }

        if ($this->canUseCrawlerDetectLibrary()) {
            return Injector::inst()->create(CrawlerDetect::class)->isCrawler($userAgent);
        }

        return preg_match($this->config()->get('user_agent_pattern'), $userAgent) === 1;
    }

    private function canUseCrawlerDetectLibrary(): bool
    {
        return $this->config()->get('use_crawler_detect_library') && class_exists(CrawlerDetect::class);
    }

}
