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
 * Uses jaybizzle/crawler-detect when it is installed. That library keeps a regularly updated list of over a thousand
 * crawler user agent patterns. Otherwise falls back to a small built-in pattern of common crawlers, monitoring tools
 * and HTTP client libraries.
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
    private static string $user_agent_pattern = '/(?<!cu)bot\b|crawl|spider|slurp|archiver|facebookexternalhit'
        . '|whatsapp\/|embedly|preview|monitor|uptime|pingdom|lighthouse|headless|phantomjs|semrush|ahrefs'
        . '|screaming frog|scrapy|curl|wget|libwww|python-requests|python-urllib|python-httpx|aiohttp|httpclient'
        . '|java\/|go-http-client|okhttp|axios|node-fetch|guzzle|postmanruntime/i';

    private ?CrawlerDetect $crawlerDetect = null;

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
            return $this->getCrawlerDetect()->isCrawler($userAgent);
        }

        return preg_match($this->config()->get('user_agent_pattern'), $userAgent) === 1;
    }

    /**
     * CrawlerDetect compiles its patterns when it is created, so one instance is reused for every check
     */
    private function getCrawlerDetect(): CrawlerDetect
    {
        if (!$this->crawlerDetect) {
            $this->crawlerDetect = Injector::inst()->create(CrawlerDetect::class);
        }

        return $this->crawlerDetect;
    }

    private function canUseCrawlerDetectLibrary(): bool
    {
        return $this->config()->get('use_crawler_detect_library') && class_exists(CrawlerDetect::class);
    }

}
