<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Tests\Analytics;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\Discoverer\Analytics\AnalyticsMiddleware;
use SilverStripe\Discoverer\Analytics\AnalyticsRequestValidator;
use SilverStripe\Discoverer\Service\Interfaces\ProcessAnalyticsAdaptor;
use UnexpectedValueException;

class AnalyticsMiddlewareTest extends SapphireTest
{

    protected $usesDatabase = false;

    protected static $required_extensions = [
        AnalyticsRequestValidator::class => [
            VetoAnalyticsExtension::class,
        ],
    ];

    private const string BASE_URL = 'https://www.example.com/';

    private const string BROWSER_USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 '
        . '(KHTML, like Gecko) Version/17.0 Safari/605.1.15';

    private const string CHROME_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, '
        . 'like Gecko) Chrome/120.0.0.0 Safari/537.36';

    private const string FIREFOX_USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64; rv:121.0) Gecko/20100101 Firefox/121.0';

    private const string CHROME_IOS_USER_AGENT = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_1 like Mac OS X) '
        . 'AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/120.0.6099.119 Mobile/15E148 Safari/604.1';

    private const string ANDROID_WEBVIEW_USER_AGENT = 'Mozilla/5.0 (Linux; Android 14; Pixel 8 Build/UQ1A; wv) '
        . 'AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/120.0.6099.193 Mobile Safari/537.36';

    private const string IOS_SAFARI_USER_AGENT = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_1 like Mac OS X) '
        . 'AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Mobile/15E148 Safari/604.1';

    private const string OLD_CHROME_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, '
        . 'like Gecko) Chrome/75.0.3770.100 Safari/537.36';

    private const string OLD_SAFARI_USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 '
        . '(KHTML, like Gecko) Version/16.3 Safari/605.1.15';

    private const array WITHOUT_FETCH_METADATA = [
        'User-Agent' => self::OLD_SAFARI_USER_AGENT,
        'Sec-Fetch-Site' => null,
        'Sec-Fetch-Mode' => null,
        'Sec-Fetch-Dest' => null,
    ];

    /**
     * Checks that are off by default and recommended in the docs
     */
    private const array RECOMMENDED_CHECKS = [
        'allowed_methods' => ['GET'],
        'require_successful_response' => true,
        'ignore_crawlers' => true,
        'require_same_origin' => true,
    ];

    private const string PAGE_BODY = 'rendered page';

    private const string QUERY_PARAM = '_searchAnalytics';

    private SpyAnalyticsAdaptor $adaptor;

    private TestHandler $logHandler;

    private array $envBackup = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->envBackup = Environment::getVariables();
        Environment::setEnv(AnalyticsMiddleware::ENV_ANALYTICS_ENABLED, '1');
        Director::config()->set('alternate_base_url', self::BASE_URL);

        $this->adaptor = new SpyAnalyticsAdaptor();
        $this->logHandler = new TestHandler();

        Injector::inst()->registerService($this->adaptor, ProcessAnalyticsAdaptor::class);

        $this->enableRecommendedChecks();
    }

    protected function tearDown(): void
    {
        Environment::setVariables($this->envBackup);

        parent::tearDown();
    }

    #[DataProvider('provideRequestsRejectedByRecommendedChecks')]
    public function testDefaultConfigRecordsEveryRequest(array $headers, string $method, int $statusCode): void
    {
        AnalyticsRequestValidator::config()->set('allowed_methods', []);
        AnalyticsRequestValidator::config()->set('require_successful_response', false);
        AnalyticsRequestValidator::config()->set('ignore_crawlers', false);
        AnalyticsRequestValidator::config()->set('require_same_origin', false);

        $this->dispatch($this->createRequest($method, $headers), $statusCode);

        $this->assertCount(1, $this->adaptor->processed);
    }

    public static function provideRequestsRejectedByRecommendedChecks(): array
    {
        return [
            'crawler' => [['User-Agent' => 'curl/8.4.0'], 'GET', 200],
            'post' => [[], 'POST', 200],
            'cross-site' => [['Sec-Fetch-Site' => 'cross-site'], 'GET', 200],
            'prefetch' => [['Sec-Purpose' => 'prefetch'], 'GET', 200],
            'modern browser without fetch metadata' => [
                ['User-Agent' => self::CHROME_USER_AGENT, 'Sec-Fetch-Site' => null],
                'GET',
                200,
            ],
            'not found' => [[], 'GET', 404],
        ];
    }

    public function testValidClickIsRecorded(): void
    {
        $response = $this->dispatch($this->createRequest());

        $this->assertSame(self::PAGE_BODY, $response->getBody());
        $this->assertCount(1, $this->adaptor->processed);

        $recorded = $this->adaptor->processed[0];
        $this->assertSame('main', $recorded->getIndexName());
        $this->assertSame('search term', $recorded->getQueryString());
        $this->assertSame('doc-1', $recorded->getDocumentId());
        $this->assertSame('request-1', $recorded->getRequestId());
    }

    public function testDifferentDocumentFromSameSearchIsRecorded(): void
    {
        $this->dispatch($this->createRequest());
        $this->dispatch($this->createRequest(param: $this->encodePayload('doc-2')));

        $this->assertCount(2, $this->adaptor->processed);
    }

    public function testRepeatedClicksAreEachRecorded(): void
    {
        $this->dispatch($this->createRequest());
        $this->dispatch($this->createRequest());
        $this->dispatch($this->createRequest(param: $this->encodePayload('doc-2')));

        $this->assertCount(3, $this->adaptor->processed);
    }

    public function testAllowedMethodsAreConfigurable(): void
    {
        AnalyticsRequestValidator::config()->set('allowed_methods', ['GET', 'POST']);

        $this->dispatch($this->createRequest('POST'));

        $this->assertCount(1, $this->adaptor->processed);
    }

    #[DataProvider('provideCrawlerUserAgents')]
    public function testCrawlerIsNotRecorded(string $userAgent): void
    {
        $response = $this->dispatch($this->createRequest(headers: ['User-Agent' => $userAgent]));

        $this->assertSame(self::PAGE_BODY, $response->getBody());
        $this->assertCount(0, $this->adaptor->processed);
    }

    public static function provideCrawlerUserAgents(): array
    {
        return [
            'empty' => [''],
            'search engine' => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'],
            'seo tool' => ['Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)'],
            'http library' => ['python-requests/2.31.0'],
            'command line' => ['curl/8.4.0'],
        ];
    }

    public function testCrawlersCanBeAllowed(): void
    {
        AnalyticsRequestValidator::config()->set('ignore_crawlers', false);

        $this->dispatch($this->createRequest(headers: ['User-Agent' => 'curl/8.4.0']));

        $this->assertCount(1, $this->adaptor->processed);
    }

    #[DataProvider('provideDisallowedMethods')]
    public function testDisallowedMethodIsNotRecorded(string $method): void
    {
        $this->dispatch($this->createRequest($method));

        $this->assertCount(0, $this->adaptor->processed);
    }

    public static function provideDisallowedMethods(): array
    {
        return [
            'POST' => ['POST'],
            'HEAD' => ['HEAD'],
        ];
    }

    #[DataProvider('provideNonNavigationFetchMetadata')]
    public function testNonSameOriginNavigationIsNotRecorded(string $fetchSite, string $fetchMode): void
    {
        $this->dispatch($this->createRequest(headers: [
            'Sec-Fetch-Site' => $fetchSite,
            'Sec-Fetch-Mode' => $fetchMode,
        ]));

        $this->assertCount(0, $this->adaptor->processed);
    }

    public static function provideNonNavigationFetchMetadata(): array
    {
        return [
            'cross-site link' => ['cross-site', 'navigate'],
            'same-site subdomain' => ['same-site', 'navigate'],
            'typed or bookmarked' => ['none', 'navigate'],
            'same-origin fetch' => ['same-origin', 'cors'],
            'same-origin prefetch' => ['same-origin', 'no-cors'],
        ];
    }

    public function testMissingFetchMetadataIsRecordedFromOlderBrowsers(): void
    {
        $this->dispatch($this->createRequest(headers: self::WITHOUT_FETCH_METADATA));

        $this->assertCount(1, $this->adaptor->processed);
    }

    #[DataProvider('provideReferers')]
    public function testRefererIsRequiredWithoutFetchMetadataWhenConfigured(?string $referer, int $expectedCount): void
    {
        AnalyticsRequestValidator::config()->set('require_referer_without_fetch_metadata', true);

        $this->dispatch($this->createRequest(headers: [...self::WITHOUT_FETCH_METADATA, 'Referer' => $referer]));

        $this->assertCount($expectedCount, $this->adaptor->processed);
    }

    public static function provideReferers(): array
    {
        return [
            'this site' => ['https://www.example.com/search?q=term', 1],
            'other site' => ['https://other.example.org/search', 0],
            'lookalike host' => ['https://www.example.com.other.example.org/search', 0],
            'missing' => [null, 0],
        ];
    }

    public function testSameOriginCheckCanBeDisabled(): void
    {
        AnalyticsRequestValidator::config()->set('require_same_origin', false);

        $this->dispatch($this->createRequest(headers: ['Sec-Fetch-Site' => 'cross-site']));

        $this->assertCount(1, $this->adaptor->processed);
    }

    public function testUserActivationIsNotRequiredByDefault(): void
    {
        $this->dispatch($this->createRequest(headers: ['User-Agent' => self::CHROME_USER_AGENT]));

        $this->assertCount(1, $this->adaptor->processed);
    }

    #[DataProvider('provideUserActivationCases')]
    public function testUserActivationIsRequiredFromSupportingBrowsersWhenConfigured(
        array $headers,
        int $expectedCount
    ): void {
        AnalyticsRequestValidator::config()->set('require_user_activation', true);

        $this->dispatch($this->createRequest(headers: $headers));

        $this->assertCount($expectedCount, $this->adaptor->processed);
    }

    public static function provideUserActivationCases(): array
    {
        return [
            'chrome click' => [['User-Agent' => self::CHROME_USER_AGENT, 'Sec-Fetch-User' => '?1'], 1],
            'chrome without activation' => [['User-Agent' => self::CHROME_USER_AGENT], 0],
            'chrome with invalid activation value' => [
                ['User-Agent' => self::CHROME_USER_AGENT, 'Sec-Fetch-User' => '?0'],
                0,
            ],
            'firefox click' => [['User-Agent' => self::FIREFOX_USER_AGENT, 'Sec-Fetch-User' => '?1'], 1],
            'firefox without activation' => [['User-Agent' => self::FIREFOX_USER_AGENT], 0],
            'safari never sends the header' => [['User-Agent' => self::BROWSER_USER_AGENT], 1],
            'chrome on ios uses webkit' => [['User-Agent' => self::CHROME_IOS_USER_AGENT], 1],
            'android webview' => [['User-Agent' => self::ANDROID_WEBVIEW_USER_AGENT], 1],
            'chrome without fetch metadata' => [
                [...self::WITHOUT_FETCH_METADATA, 'User-Agent' => self::OLD_CHROME_USER_AGENT],
                1,
            ],
        ];
    }

    #[DataProvider('provideSpeculativeLoads')]
    public function testSpeculativeLoadIsNotRecorded(array $headers): void
    {
        $this->dispatch($this->createRequest(headers: $headers));

        $this->assertCount(0, $this->adaptor->processed);
    }

    public static function provideSpeculativeLoads(): array
    {
        return [
            'speculation rules prefetch' => [['Sec-Purpose' => 'prefetch']],
            'speculation rules prerender' => [['Sec-Purpose' => 'prefetch;prerender']],
            'legacy prefetch' => [['Purpose' => 'prefetch']],
        ];
    }

    public function testIframeNavigationIsNotRecorded(): void
    {
        $this->dispatch($this->createRequest(headers: ['Sec-Fetch-Dest' => 'iframe']));

        $this->assertCount(0, $this->adaptor->processed);
    }

    #[DataProvider('provideUserAgentsWithoutFetchMetadata')]
    public function testMissingFetchMetadataDependsOnClaimedBrowser(string $userAgent, int $expectedCount): void
    {
        $this->dispatch($this->createRequest(headers: [...self::WITHOUT_FETCH_METADATA, 'User-Agent' => $userAgent]));

        $this->assertCount($expectedCount, $this->adaptor->processed);
    }

    public static function provideUserAgentsWithoutFetchMetadata(): array
    {
        return [
            'chrome 120 always sends it' => [self::CHROME_USER_AGENT, 0],
            'firefox 121 always sends it' => [self::FIREFOX_USER_AGENT, 0],
            'safari 17 always sends it' => [self::BROWSER_USER_AGENT, 0],
            'ios safari 17 always sends it' => [self::IOS_SAFARI_USER_AGENT, 0],
            'chrome 75 predates it' => [self::OLD_CHROME_USER_AGENT, 1],
            'safari 16.3 predates it' => [self::OLD_SAFARI_USER_AGENT, 1],
            'chrome on ios is excluded' => [self::CHROME_IOS_USER_AGENT, 1],
            'android webview is excluded' => [self::ANDROID_WEBVIEW_USER_AGENT, 1],
        ];
    }

    public function testMissingFetchMetadataIsRecordedOverHttp(): void
    {
        Director::config()->set('alternate_base_url', 'http://www.example.com/');

        $this->dispatch($this->createRequest(headers: [
            ...self::WITHOUT_FETCH_METADATA,
            'User-Agent' => self::CHROME_USER_AGENT,
        ]));

        $this->assertCount(1, $this->adaptor->processed);
    }

    public function testLoggerFailureStillRendersPage(): void
    {
        $this->adaptor->exception = new RuntimeException('Search service unavailable');
        $middleware = AnalyticsMiddleware::create();
        $middleware->setLogger(new Logger('test', [new class extends TestHandler {

            protected function write(LogRecord $record): void
            {
                throw new UnexpectedValueException('Log file is not writable');
            }

        }]));

        $response = $middleware->process($this->createRequest(), function (): HTTPResponse {
            return HTTPResponse::create(self::PAGE_BODY, 200);
        });

        $this->assertSame(self::PAGE_BODY, $response->getBody());
    }

    #[DataProvider('provideUnsuccessfulStatusCodes')]
    public function testUnsuccessfulResponseIsNotRecorded(int $statusCode): void
    {
        $response = $this->dispatch($this->createRequest(), $statusCode);

        $this->assertSame($statusCode, $response->getStatusCode());
        $this->assertCount(0, $this->adaptor->processed);
    }

    public static function provideUnsuccessfulStatusCodes(): array
    {
        return [
            'redirect' => [302],
            'not found' => [404],
            'server error' => [500],
        ];
    }

    public function testUnsuccessfulResponseCanBeRecordedWhenConfigured(): void
    {
        AnalyticsRequestValidator::config()->set('require_successful_response', false);

        $this->dispatch($this->createRequest(), 404);

        $this->assertCount(1, $this->adaptor->processed);
    }

    public function testExtensionCanVetoClick(): void
    {
        $this->dispatch($this->createRequest(headers: [VetoAnalyticsExtension::HEADER => '1']));

        $this->assertCount(0, $this->adaptor->processed);
    }

    public function testAdaptorFailureStillRendersPageAndLogsError(): void
    {
        $this->adaptor->exception = new RuntimeException('Search service unavailable');

        $response = $this->dispatch($this->createRequest());

        $this->assertSame(self::PAGE_BODY, $response->getBody());
        $this->assertTrue($this->logHandler->hasErrorThatContains('Search service unavailable'));
    }

    #[DataProvider('provideMalformedParams')]
    public function testMalformedParamIsIgnored(string $param): void
    {
        $response = $this->dispatch($this->createRequest(param: $param));

        $this->assertSame(self::PAGE_BODY, $response->getBody());
        $this->assertCount(0, $this->adaptor->processed);
        $this->assertFalse($this->logHandler->hasErrorRecords());
    }

    public static function provideMalformedParams(): array
    {
        return [
            'not base64' => ['%%%'],
            'not json' => [base64_encode('not json')],
            'json scalar' => [base64_encode('"text"')],
        ];
    }

    public function testNothingIsRecordedWhenAnalyticsDisabled(): void
    {
        Environment::setEnv(AnalyticsMiddleware::ENV_ANALYTICS_ENABLED, false);

        $response = $this->dispatch($this->createRequest());

        $this->assertSame(self::PAGE_BODY, $response->getBody());
        $this->assertCount(0, $this->adaptor->processed);
    }

    private function enableRecommendedChecks(): void
    {
        foreach (self::RECOMMENDED_CHECKS as $key => $value) {
            AnalyticsRequestValidator::config()->set($key, $value);
        }
    }

    private function dispatch(HTTPRequest $request, int $statusCode = 200): HTTPResponse
    {
        $middleware = AnalyticsMiddleware::create();
        $middleware->setLogger(new Logger('test', [$this->logHandler]));

        return $middleware->process($request, function () use ($statusCode): HTTPResponse {
            return HTTPResponse::create(self::PAGE_BODY, $statusCode);
        });
    }

    /**
     * Builds a request that passes every check by default: a same-origin link click from a desktop browser. Pass a
     * null header value to remove a default header.
     *
     * @param array<string, string|null> $headers
     */
    private function createRequest(
        string $method = 'GET',
        array $headers = [],
        ?string $param = null
    ): HTTPRequest {
        $request = new HTTPRequest($method, 'results/page', [self::QUERY_PARAM => $param ?? $this->encodePayload()]);

        $headers = array_merge(
            [
                'User-Agent' => self::BROWSER_USER_AGENT,
                'Sec-Fetch-Site' => 'same-origin',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Dest' => 'document',
            ],
            $headers
        );

        foreach ($headers as $name => $value) {
            if ($value !== null) {
                $request->addHeader($name, $value);
            }
        }

        return $request;
    }

    private function encodePayload(string $documentId = 'doc-1'): string
    {
        $data = AnalyticsData::create();
        $data->setIndexName('main');
        $data->setQueryString('search term');
        $data->setDocumentId($documentId);
        $data->setRequestId('request-1');

        parse_str($data->forTemplate(), $query);

        return $query[self::QUERY_PARAM];
    }

}
