<?php

namespace SilverStripe\Discoverer\Tests\Service\Results;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\Discoverer\Service\Results\Field;
use SilverStripe\Discoverer\Service\Results\Record;

class RecordTest extends SapphireTest
{

    public function testAnalyticsData(): void
    {
        $record = Record::create();
        $record->setAnalyticsData(AnalyticsData::create());

        $this->assertInstanceOf(AnalyticsData::class, $record->getAnalyticsData());
    }

    /**
     * @dataProvider provideLinks
     */
    public function testGetDecoratedLink(?AnalyticsData $analyticsData, string $link, string $expectedLink): void
    {
        if (InstalledVersions::satisfies(new VersionParser(), 'silverstripe/framework', '<5.2.20') && strpos(
            $link,
            'www'
        ) !== false) {
            // skip external link tests on older versions as the behaviour has changed
            return;
        }

        $record = Record::create();
        $record->setAnalyticsData($analyticsData);

        $this->assertEquals($expectedLink, $record->getDecoratedLink($link));
    }

    public function provideLinks(): array
    {
        // SapphireTest doesn't like the use of injector in data providers
        $analyticsData = new AnalyticsData();
        $analyticsData->setEngineName('test');
        $analyticsData->setQueryString('query');
        $analyticsData->setDocumentId('documentId');
        $analyticsData->setRequestId('requestId');

        $expectedAnalytics = $analyticsData->forTemplate();

        return [
            [null, '', ''],
            [null, '/', '/'],
            [null, '?one=two', '?one=two'],
            [null, '/?one=two', '/?one=two'],
            [null, '/page', '/page'],
            [null, '/page/', '/page/'],
            [null, '/page?one=two', '/page?one=two'],
            [null, '/page/?one=two', '/page/?one=two'],
            [$analyticsData, '', sprintf('/?%s', $expectedAnalytics)],
            [$analyticsData, '/', sprintf('/?%s', $expectedAnalytics)],
            [$analyticsData, '?one=two', sprintf('/?one=two&%s', $expectedAnalytics)],
            [$analyticsData, '/?one=two', sprintf('/?one=two&%s', $expectedAnalytics)],
            [$analyticsData, '/page', sprintf('/page/?%s', $expectedAnalytics)],
            [$analyticsData, '/page/', sprintf('/page/?%s', $expectedAnalytics)],
            [$analyticsData, '/page?one=two', sprintf('/page/?one=two&%s', $expectedAnalytics)],
            [$analyticsData, '/page/?one=two', sprintf('/page/?one=two&%s', $expectedAnalytics)],
            [
                $analyticsData,
                'https://www.localhost.com',
                sprintf('https://www.localhost.com?%s', $expectedAnalytics),
            ],
            [
                $analyticsData,
                'https://www.localhost.com/',
                sprintf('https://www.localhost.com/?%s', $expectedAnalytics),
            ],
            [
                $analyticsData,
                'https://www.example.com?one=two',
                sprintf('https://www.example.com?one=two&%s', $expectedAnalytics),
            ],
            [
                $analyticsData,
                'https://www.localhost.com/?one=two',
                sprintf('https://www.localhost.com/?one=two&%s', $expectedAnalytics),
            ],
            [
                $analyticsData,
                'https://www.localhost.com/page',
                sprintf('https://www.localhost.com/page?%s', $expectedAnalytics),
            ],
            [
                $analyticsData,
                'https://www.localhost.com/page/',
                sprintf('https://www.localhost.com/page/?%s', $expectedAnalytics),
            ],
            [
                $analyticsData,
                'https://www.localhost.com/page?one=two',
                sprintf('https://www.localhost.com/page?one=two&%s', $expectedAnalytics),
            ],
            [
                $analyticsData,
                'https://www.localhost.com/page/?one=two',
                sprintf('https://www.localhost.com/page/?one=two&%s', $expectedAnalytics),
            ],
        ];
    }

    public function testDynamicFields(): void
    {
        $record = Record::create();
        $record->Title = Field::create('title_raw');
        $record->TwoWords = Field::create('two_words_raw');

        $this->assertEquals('title_raw', $record->Title->getRaw());
        $this->assertEquals('two_words_raw', $record->TwoWords->getRaw());
    }

    public function testInvalidDynamicField(): void
    {
        $this->expectExceptionMessage(sprintf('Field value must be an instance of %s', Field::class));

        $record = Record::create();
        // This should throw our Exception
        $record->Title = 'Invalid';
    }

    protected function setUp(): void
    {
        parent::setUp();

        Config::nest();
        Controller::config()->set('add_trailing_slash', true);
    }

    protected function tearDown(): void
    {
        Config::unnest();

        parent::tearDown();
    }

}
