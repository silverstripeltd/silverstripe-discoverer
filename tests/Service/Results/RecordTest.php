<?php

namespace SilverStripe\Discoverer\Tests\Service\Results;

use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Assets\Dev\TestAssetStore;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\Discoverer\Service\Results\Field;
use SilverStripe\Discoverer\Service\Results\Record;
use SilverStripe\View\SSViewer;

class RecordTest extends SapphireTest
{

    protected function setUp(): void
    {
        parent::setUp();
        SSViewer::config()->set('source_file_comments', false);
        TestAssetStore::activate('RecordTest');
    }

    protected function tearDown(): void
    {
        TestAssetStore::reset();
        parent::tearDown();
    }

    public function testAnalyticsData(): void
    {
        $record = Record::create();
        $record->setAnalyticsData(AnalyticsData::create());

        $this->assertInstanceOf(AnalyticsData::class, $record->getAnalyticsData());
    }

    #[DataProvider('provideLinks')]
    public function testGetDecoratedLink(bool $withAnalytics, string $link, string $expectedLinkFormat): void
    {
        $analyticsData = $withAnalytics ? $this->createAnalyticsData() : null;
        $record = Record::create();
        $record->setAnalyticsData($analyticsData);

        $expectedLink = sprintf($expectedLinkFormat, $analyticsData?->forTemplate());

        $this->assertEquals($expectedLink, $record->getDecoratedLink($link));
    }

    public function testGetDecoratedLinkTemplate(): void
    {
        $actual = Record::create()->renderWith('DecoratedLink', ['Link' => Field::create('http://www.example.com')]);

        $this->assertSame('<a href="SilverStripe\Discoverer\Service\Results\Field">Link</a><a href="http://www.example.com">Link</a>', trim($actual->RAW()));
    }

    /**
     * Expected links are sprintf formats, where %s is replaced by the encoded analytics param
     */
    public static function provideLinks(): array
    {
        return [
            [false, '', '/'],
            [false, '/', '/'],
            [false, '?one=two', '/?one=two'],
            [false, '/?one=two', '/?one=two'],
            [false, '/page', '/page'],
            [false, '/page/', '/page'],
            [false, '/page?one=two', '/page?one=two'],
            [false, '/page/?one=two', '/page?one=two'],
            [true, '', '/?%s'],
            [true, '/', '/?%s'],
            [true, '?one=two', '/?one=two&%s'],
            [true, '/?one=two', '/?one=two&%s'],
            [true, '/page', '/page?%s'],
            [true, '/page/', '/page?%s'],
            [true, '/page?one=two', '/page?one=two&%s'],
            [true, '/page/?one=two', '/page?one=two&%s'],
        ];
    }

    private function createAnalyticsData(): AnalyticsData
    {
        $analyticsData = AnalyticsData::create();
        $analyticsData->setIndexName('test');
        $analyticsData->setQueryString('query');
        $analyticsData->setDocumentId('documentId');
        $analyticsData->setRequestId('requestId');

        return $analyticsData;
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
