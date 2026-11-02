<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Tests\Analytics;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\Discoverer\Analytics\AnalyticsMiddleware;
use SilverStripe\Discoverer\Analytics\AnalyticsSigner;
use SilverStripe\Discoverer\Service\Interfaces\ProcessAnalyticsAdaptor;
use SilverStripe\ORM\FieldType\DBDatetime;

class AnalyticsMiddlewareTest extends SapphireTest
{

    protected $usesDatabase = false;

    private const string PAGE_BODY = 'rendered page';

    private SpyAnalyticsAdaptor $adaptor;

    private TestHandler $logHandler;

    private array $envBackup = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->envBackup = Environment::getVariables();
        Environment::setEnv(AnalyticsMiddleware::ENV_ANALYTICS_ENABLED, '1');
        Environment::setEnv(AnalyticsSigner::ENV_SECRET, '');
        DBDatetime::set_mock_now('2026-01-01 12:00:00');

        $this->adaptor = new SpyAnalyticsAdaptor();
        $this->logHandler = new TestHandler();

        Injector::inst()->registerService($this->adaptor, ProcessAnalyticsAdaptor::class);
    }

    protected function tearDown(): void
    {
        Environment::setVariables($this->envBackup);
        DBDatetime::clear_mock_now();

        parent::tearDown();
    }

    public function testClickIsRecorded(): void
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

    public function testUnsignedClickIsRecordedWhenSigningDisabled(): void
    {
        $payload = $this->decodeParam($this->encodePayload());

        $this->assertArrayNotHasKey(AnalyticsData::KEY_TIMESTAMP, $payload);
        $this->assertArrayNotHasKey(AnalyticsData::KEY_SIGNATURE, $payload);

        $this->dispatch($this->createRequest());

        $this->assertCount(1, $this->adaptor->processed);
    }

    public function testSignedClickIsRecorded(): void
    {
        $this->enableSigning();

        $response = $this->dispatch($this->createRequest());

        $this->assertSame(self::PAGE_BODY, $response->getBody());
        $this->assertCount(1, $this->adaptor->processed);
    }

    #[DataProvider('provideInvalidSignedPayloads')]
    public function testInvalidSignedClickIsNotRecorded(string $case): void
    {
        $this->enableSigning();

        $payload = $this->decodeParam($this->encodePayload());

        match ($case) {
            'tampered' => $payload['documentId'] = 'other-doc',
            'missing signature' => $payload = array_diff_key($payload, [AnalyticsData::KEY_SIGNATURE => true]),
            'missing timestamp' => $payload = array_diff_key($payload, [AnalyticsData::KEY_TIMESTAMP => true]),
            'wrong secret' => $payload[AnalyticsData::KEY_SIGNATURE] = hash_hmac('sha256', 'anything', 'guess'),
            'timestamp changed' => $payload[AnalyticsData::KEY_TIMESTAMP] += 10,
        };

        $response = $this->dispatch($this->createRequest(base64_encode(json_encode($payload))));

        $this->assertSame(self::PAGE_BODY, $response->getBody());
        $this->assertCount(0, $this->adaptor->processed);
        $this->assertTrue($this->logHandler->hasDebugThatContains('Search analytics click not recorded'));
    }

    public static function provideInvalidSignedPayloads(): array
    {
        return [
            'tampered' => ['tampered'],
            'missing signature' => ['missing signature'],
            'missing timestamp' => ['missing timestamp'],
            'wrong secret' => ['wrong secret'],
            'timestamp changed' => ['timestamp changed'],
        ];
    }

    public function testUnsignedClickIsNotRecordedWhenSigningEnabled(): void
    {
        $param = $this->encodePayload();
        $this->enableSigning();

        $this->dispatch($this->createRequest($param));

        $this->assertCount(0, $this->adaptor->processed);
    }

    public function testExpiredSignedClickIsNotRecorded(): void
    {
        $this->enableSigning();
        $param = $this->encodePayload();

        DBDatetime::set_mock_now('2026-01-01 13:00:01');
        $this->dispatch($this->createRequest($param));

        $this->assertCount(0, $this->adaptor->processed);
    }

    public function testSignedClickWithinMaxLinkAgeIsRecorded(): void
    {
        $this->enableSigning();
        $param = $this->encodePayload();

        DBDatetime::set_mock_now('2026-01-01 13:00:00');
        $this->dispatch($this->createRequest($param));

        $this->assertCount(1, $this->adaptor->processed);
    }

    public function testMaxLinkAgeCanBeDisabled(): void
    {
        AnalyticsMiddleware::config()->set('max_link_age', 0);
        $this->enableSigning();
        $param = $this->encodePayload();

        DBDatetime::set_mock_now('2026-02-01 12:00:00');
        $this->dispatch($this->createRequest($param));

        $this->assertCount(1, $this->adaptor->processed);
    }

    public function testFutureSignedClickIsNotRecorded(): void
    {
        $this->enableSigning();
        DBDatetime::set_mock_now('2026-01-01 12:05:00');
        $param = $this->encodePayload();

        DBDatetime::set_mock_now('2026-01-01 12:00:00');
        $this->dispatch($this->createRequest($param));

        $this->assertCount(0, $this->adaptor->processed);
    }

    public function testAdaptorFailureStillRendersPageAndLogsError(): void
    {
        $this->adaptor->exception = new RuntimeException('Search service unavailable');

        $response = $this->dispatch($this->createRequest());

        $this->assertSame(self::PAGE_BODY, $response->getBody());
        $this->assertTrue($this->logHandler->hasErrorThatContains('Search service unavailable'));
    }

    public function testNothingIsRecordedWhenAnalyticsDisabled(): void
    {
        Environment::setEnv(AnalyticsMiddleware::ENV_ANALYTICS_ENABLED, false);

        $response = $this->dispatch($this->createRequest());

        $this->assertSame(self::PAGE_BODY, $response->getBody());
        $this->assertCount(0, $this->adaptor->processed);
    }

    private function enableSigning(): void
    {
        Environment::setEnv(AnalyticsSigner::ENV_SECRET, 'test-secret');
    }

    private function dispatch(HTTPRequest $request): HTTPResponse
    {
        $middleware = AnalyticsMiddleware::create();
        $middleware->setLogger(new Logger('test', [$this->logHandler]));

        return $middleware->process($request, static fn (): HTTPResponse => HTTPResponse::create(self::PAGE_BODY));
    }

    private function createRequest(?string $param = null): HTTPRequest
    {
        return new HTTPRequest('GET', 'results/page', [
            AnalyticsData::QUERY_PARAM => $param ?? $this->encodePayload(),
        ]);
    }

    private function encodePayload(): string
    {
        $data = AnalyticsData::create();
        $data->setIndexName('main');
        $data->setQueryString('search term');
        $data->setDocumentId('doc-1');
        $data->setRequestId('request-1');

        parse_str($data->forTemplate(), $query);

        return $query[AnalyticsData::QUERY_PARAM];
    }

    private function decodeParam(string $param): array
    {
        return json_decode(base64_decode($param), true);
    }

}
