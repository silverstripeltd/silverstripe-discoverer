<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Tests\Analytics;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\Discoverer\Analytics\AnalyticsMiddleware;
use SilverStripe\Discoverer\Analytics\AnalyticsSigner;
use SilverStripe\Discoverer\Analytics\ClickController;
use SilverStripe\Discoverer\Service\Interfaces\ProcessAnalyticsAdaptor;
use SilverStripe\Discoverer\Service\Results\Record;

class ClickControllerTest extends FunctionalTest
{

    protected $usesDatabase = false;

    protected $autoFollowRedirection = false;

    protected static $required_extensions = [
        ClickController::class => [
            VetoClickExtension::class,
        ],
    ];

    private const string BASE_URL = 'https://www.example.com/';

    private SpyAnalyticsAdaptor $adaptor;

    private TestHandler $logHandler;

    private array $envBackup = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->envBackup = Environment::getVariables();
        Environment::setEnv(AnalyticsMiddleware::ENV_ANALYTICS_ENABLED, '1');
        Environment::setEnv(AnalyticsSigner::ENV_SECRET, 'test-secret');
        Director::config()->set('alternate_base_url', self::BASE_URL);
        ClickController::config()->set('enabled', true);
        VetoClickExtension::$veto = false;

        $this->adaptor = new SpyAnalyticsAdaptor();
        $this->logHandler = new TestHandler();

        Injector::inst()->registerService($this->adaptor, ProcessAnalyticsAdaptor::class);
        Injector::inst()->registerService(
            new Logger('test', [$this->logHandler]),
            LoggerInterface::class . '.errorhandler'
        );
    }

    protected function tearDown(): void
    {
        Environment::setVariables($this->envBackup);

        parent::tearDown();
    }

    #[DataProvider('provideDestinations')]
    public function testValidClickIsRecordedAndRedirected(string $destination, string $expectedLocation): void
    {
        $response = $this->get($this->createRecord()->getDecoratedLink($destination));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame($expectedLocation, $response->getHeader('Location'));
        $this->assertCount(1, $this->adaptor->processed);
        $this->assertSame('doc-1', $this->adaptor->processed[0]->getDocumentId());
    }

    public static function provideDestinations(): array
    {
        return [
            'page on this site' => ['/about-us', 'https://www.example.com/about-us'],
            'file on this site' => ['/assets/reports/annual.pdf', 'https://www.example.com/assets/reports/annual.pdf'],
            'external site' => ['https://other.example.org/page?a=b', 'https://other.example.org/page?a=b'],
        ];
    }

    public function testRedirectIsNotCachedOrIndexed(): void
    {
        $response = $this->get($this->createRecord()->getDecoratedLink('/about-us'));

        $this->assertStringContainsString('no-store', (string) $response->getHeader('Cache-Control'));
        $this->assertSame('noindex, nofollow', $response->getHeader('X-Robots-Tag'));
    }

    public function testTamperedDataIsNotRecordedButStillRedirectsOnSite(): void
    {
        $url = $this->replaceData($this->createRecord()->getDecoratedLink('/about-us'), ['documentId' => 'doc-2']);

        $response = $this->get($url);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://www.example.com/about-us', $response->getHeader('Location'));
        $this->assertCount(0, $this->adaptor->processed);
    }

    #[DataProvider('provideUntrustedExternalDestinations')]
    public function testUntrustedExternalDestinationRedirectsHome(string $url): void
    {
        $response = $this->get($url);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(Director::absoluteBaseURL(), $response->getHeader('Location'));
        $this->assertCount(0, $this->adaptor->processed);
    }

    public static function provideUntrustedExternalDestinations(): array
    {
        return [
            'unsigned' => ['_search-click?url=' . urlencode('https://evil.example.net/')],
            'protocol relative' => ['_search-click?url=' . urlencode('//evil.example.net/')],
        ];
    }

    public function testChangedDestinationRedirectsHome(): void
    {
        $url = $this->createRecord()->getDecoratedLink('https://other.example.org/page');
        $url = str_replace(urlencode('https://other.example.org/page'), urlencode('https://evil.example.net/'), $url);

        $response = $this->get($url);

        $this->assertSame(Director::absoluteBaseURL(), $response->getHeader('Location'));
        $this->assertCount(0, $this->adaptor->processed);
    }

    #[DataProvider('provideUnsafeSchemes')]
    public function testUnsafeSchemeRedirectsHomeEvenWhenSigned(string $destination): void
    {
        $response = $this->get($this->createRecord()->getDecoratedLink($destination));

        $this->assertSame(Director::absoluteBaseURL(), $response->getHeader('Location'));
    }

    public static function provideUnsafeSchemes(): array
    {
        return [
            'javascript' => ['javascript:alert(1)'],
            'data' => ['data:text/html,hello'],
        ];
    }

    public function testMissingDestinationRedirectsHome(): void
    {
        $response = $this->get('_search-click');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(Director::absoluteBaseURL(), $response->getHeader('Location'));
    }

    public function testRepeatedClicksAreEachRecorded(): void
    {
        $url = $this->createRecord()->getDecoratedLink('/about-us');

        $this->get($url);
        $this->get($url);

        $this->assertCount(2, $this->adaptor->processed);
    }

    public function testPostIsRedirectedWithoutRecording(): void
    {
        $response = $this->post($this->createRecord()->getDecoratedLink('/about-us'), []);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertCount(0, $this->adaptor->processed);
    }

    public function testNothingIsRecordedWhenAnalyticsDisabled(): void
    {
        $url = $this->createRecord()->getDecoratedLink('/about-us');
        Environment::setEnv(AnalyticsMiddleware::ENV_ANALYTICS_ENABLED, false);

        $response = $this->get($url);

        $this->assertSame('https://www.example.com/about-us', $response->getHeader('Location'));
        $this->assertCount(0, $this->adaptor->processed);
    }

    public function testExtensionCanSkipRecording(): void
    {
        VetoClickExtension::$veto = true;

        $response = $this->get($this->createRecord()->getDecoratedLink('/about-us'));

        $this->assertSame('https://www.example.com/about-us', $response->getHeader('Location'));
        $this->assertCount(0, $this->adaptor->processed);
    }

    public function testAdaptorFailureStillRedirectsAndLogsError(): void
    {
        $this->adaptor->exception = new RuntimeException('Search service unavailable');

        $response = $this->get($this->createRecord()->getDecoratedLink('/about-us'));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://www.example.com/about-us', $response->getHeader('Location'));
        $this->assertTrue($this->logHandler->hasErrorThatContains('Search service unavailable'));
    }

    public function testDecoratedLinkUsesRouteWhenActive(): void
    {
        $record = $this->createRecord();
        $link = $record->getDecoratedLink('/about-us');

        $this->assertStringStartsWith('/_search-click?url=' . urlencode('/about-us') . '&', $link);
        $this->assertSame($link, $record->getClickLink('/about-us'));
    }

    #[DataProvider('provideInactiveRouteSettings')]
    public function testDecoratedLinkKeepsParamWhenRouteInactive(bool $enabled, string $secret): void
    {
        ClickController::config()->set('enabled', $enabled);
        Environment::setEnv(AnalyticsSigner::ENV_SECRET, $secret);
        $record = $this->createRecord();

        $this->assertStringStartsWith('/about-us?' . AnalyticsData::QUERY_PARAM . '=', $record->getDecoratedLink('/about-us'));
        $this->assertNull($record->getClickLink('/about-us'));
    }

    public static function provideInactiveRouteSettings(): array
    {
        return [
            'disabled' => [false, 'test-secret'],
            'enabled without a secret' => [true, ''],
        ];
    }

    private function createRecord(): Record
    {
        $analyticsData = AnalyticsData::create();
        $analyticsData->setIndexName('main');
        $analyticsData->setQueryString('search term');
        $analyticsData->setDocumentId('doc-1');
        $analyticsData->setRequestId('request-1');

        $record = Record::create();
        $record->setAnalyticsData($analyticsData);

        return $record;
    }

    /**
     * Replaces fields in the encoded analytics data of a click URL, keeping the original signature
     *
     * @param array<string, mixed> $fields
     */
    private function replaceData(string $url, array $fields): string
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $data = json_decode(base64_decode($query[AnalyticsData::QUERY_PARAM]), true);
        $query[AnalyticsData::QUERY_PARAM] = base64_encode(json_encode(array_merge($data, $fields)));

        return sprintf('%s?%s', parse_url($url, PHP_URL_PATH), http_build_query($query));
    }

}
