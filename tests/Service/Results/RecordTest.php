<?php

namespace SilverStripe\Discoverer\Tests\Service\Results;

use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Control\Controller;
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

    #[DataProvider('provideLinks')]
    public function testGetDecoratedLink(?AnalyticsData $analyticsData, string $link, string $expectedLink): void
    {
        $record = Record::create();
        $record->setAnalyticsData($analyticsData);

        $this->assertEquals($expectedLink, $record->getDecoratedLink($link));
    }

    public static function provideLinks(): array
    {
        // SapphireTest doesn't like the use of injector in data providers
        $analyticsData = new AnalyticsData();
        $analyticsData->setIndexName('test');
        $analyticsData->setQueryString('query');
        $analyticsData->setDocumentId('documentId');
        $analyticsData->setRequestId('requestId');

        $expectedAnalytics = $analyticsData->forTemplate();

        return [
            [null, '', '/'],
            [null, '/', '/'],
            [null, '?one=two', '/?one=two'],
            [null, '/?one=two', '/?one=two'],
            [null, '/page', '/page'],
            [null, '/page/', '/page'],
            [null, '/page?one=two', '/page?one=two'],
            [null, '/page/?one=two', '/page?one=two'],
            [$analyticsData, '', sprintf('/?%s', $expectedAnalytics)],
            [$analyticsData, '/', sprintf('/?%s', $expectedAnalytics)],
            [$analyticsData, '?one=two', sprintf('/?one=two&%s', $expectedAnalytics)],
            [$analyticsData, '/?one=two', sprintf('/?one=two&%s', $expectedAnalytics)],
            [$analyticsData, '/page', sprintf('/page?%s', $expectedAnalytics)],
            [$analyticsData, '/page/', sprintf('/page?%s', $expectedAnalytics)],
            [$analyticsData, '/page?one=two', sprintf('/page?one=two&%s', $expectedAnalytics)],
            [$analyticsData, '/page/?one=two', sprintf('/page?one=two&%s', $expectedAnalytics)],
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

    public function testJsonSerialize(): void
    {
        $analyticsData = AnalyticsData::create();
        $analyticsData->setIndexName('our-index-main');
        $analyticsData->setQueryString('search string');
        $analyticsData->setDocumentId('abc123');
        $analyticsData->setRequestId('123abc');

        $record = Record::create();
        $record->setAnalyticsData($analyticsData);
        $record->CustomField = Field::create('raw', 'formatted');

        $expected = [
            'AnalyticsData' => [
                'indexName' => 'our-index-main',
                'queryString' => 'search string',
                'documentId' => 'abc123',
                'requestId' => '123abc',
            ],
            'CustomFieldText' => [
                'raw' => 'raw',
                'formatted' => 'formatted',
            ],
        ];

        $this->assertEqualsCanonicalizing($expected, $record->jsonSerialize());
    }

    public function testJsonSerializeEmpty(): void
    {
        $record = Record::create();

        $expected = [
            'AnalyticsData' => null,
        ];

        $this->assertEqualsCanonicalizing($expected, $record->jsonSerialize());
    }

}
