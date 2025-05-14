<?php

namespace SilverStripe\Discoverer\Tests\Analytics;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Analytics\AnalyticsData;

class AnalyticsDataTest extends SapphireTest
{

    public function testForTemplate(): void
    {
        $data = AnalyticsData::create();
        $data->setIndexName('our-index-main');
        $data->setQueryString('search string');
        $data->setDocumentId('abc123');
        $data->setRequestId('123abc');

        $expectedData = [
            'indexName' => 'our-index-main',
            'queryString' => 'search string',
            'documentId' => 'abc123',
            'requestId' => '123abc',
        ];
        $expectedParams = [
            '_searchAnalytics' => base64_encode(json_encode($expectedData)),
        ];
        $expectedResult = http_build_query($expectedParams);

        $this->assertEquals($expectedResult, $data->forTemplate());
    }

    public function testForTemplateEmptyData(): void
    {
        $data = AnalyticsData::create();

        $this->assertEquals($data->forTemplate(), '');
    }

}
