<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Tests\Analytics;

use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Analytics\CrawlerDetector;

class CrawlerDetectorTest extends SapphireTest
{

    protected $usesDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Exercise the built-in pattern, regardless of whether jaybizzle/crawler-detect is installed
        CrawlerDetector::config()->set('use_crawler_detect_library', false);
    }

    #[DataProvider('provideUserAgents')]
    public function testIsCrawler(?string $userAgent, bool $expected): void
    {
        $this->assertSame($expected, CrawlerDetector::create()->isCrawler($userAgent));
    }

    public static function provideUserAgents(): array
    {
        return [
            'null' => [null, true],
            'empty' => ['', true],
            'whitespace' => ['   ', true],
            'googlebot' => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', true],
            'bingbot' => ['Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)', true],
            'semrush' => ['Mozilla/5.0 (compatible; SemrushBot/7~bl; +http://www.semrush.com/bot.html)', true],
            'uptime monitor' => ['Mozilla/5.0 (compatible; UptimeRobot/2.0; http://www.uptimerobot.com/)', true],
            'headless chrome' => ['Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 HeadlessChrome/120.0 Safari/537.36', true],
            'guzzle' => ['GuzzleHttp/7', true],
            'wget' => ['Wget/1.21.4', true],
            'whatsapp link preview' => ['WhatsApp/2.23.20.0', true],
            'python httpx' => ['python-httpx/0.27.0', true],
            'aiohttp' => ['Python/3.12 aiohttp/3.9.5', true],
            'postman' => ['PostmanRuntime/7.39.0', true],
            'cubot handset is a browser' => ['Mozilla/5.0 (Linux; Android 12; CUBOT X50) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36', false],
            'safari' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15', false],
            'chrome' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36', false],
            'firefox' => ['Mozilla/5.0 (X11; Linux x86_64; rv:121.0) Gecko/20100101 Firefox/121.0', false],
            'mobile safari' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Mobile/15E148 Safari/604.1', false],
        ];
    }

    public function testPatternIsConfigurable(): void
    {
        CrawlerDetector::config()->set('user_agent_pattern', '/internal-checker/i');
        $detector = CrawlerDetector::create();

        $this->assertTrue($detector->isCrawler('Internal-Checker/1.0'));
        $this->assertFalse($detector->isCrawler('curl/8.4.0'));
    }

}
