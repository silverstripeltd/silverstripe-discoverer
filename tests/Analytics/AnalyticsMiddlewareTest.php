<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Tests\Analytics;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
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
    }

    protected function tearDown(): void
    {
        Environment::setVariables($this->envBackup);

        parent::tearDown();
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

    public function testMissingFetchMetadataIsRecordedByDefault(): void
    {
        $this->dispatch($this->createRequest(headers: ['Sec-Fetch-Site' => null, 'Sec-Fetch-Mode' => null]));

        $this->assertCount(1, $this->adaptor->processed);
    }

    #[DataProvider('provideReferers')]
    public function testRefererIsRequiredWithoutFetchMetadataWhenConfigured(?string $referer, int $expectedCount): void
    {
        AnalyticsRequestValidator::config()->set('require_referer_without_fetch_metadata', true);

        $this->dispatch($this->createRequest(headers: [
            'Sec-Fetch-Site' => null,
            'Sec-Fetch-Mode' => null,
            'Referer' => $referer,
        ]));

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
