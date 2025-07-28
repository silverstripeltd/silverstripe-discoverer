<?php

namespace SilverStripe\Discoverer\Tests\Service\Results;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\Discoverer\Service\Results\Field;
use SilverStripe\Discoverer\Service\Results\Record;
use SilverStripe\Discoverer\Service\Results\Records;
use SilverStripe\Model\List\ArrayList;

class RecordsTest extends SapphireTest
{

    public function testJsonSerialize(): void
    {
        $analyticsData = AnalyticsData::create();
        $analyticsData->setIndexName('our-index-main');
        $analyticsData->setQueryString('search string');
        $analyticsData->setDocumentId('abc123');
        $analyticsData->setRequestId('123abc');

        $recordOne = Record::create();
        $recordOne->setAnalyticsData($analyticsData);
        $recordOne->CustomField = Field::create('raw', 'formatted');

        $recordTwo = Record::create();
        $recordTwo->CustomField = Field::create('raw2', 'formatted2');

        $records = Records::create(ArrayList::create([$recordOne, $recordTwo]));

        $expected = [
            [
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
            ],
            [
                'AnalyticsData' => null,
                'CustomFieldText' => [
                    'raw' => 'raw2',
                    'formatted' => 'formatted2',
                ],
            ],
        ];

        $this->assertEqualsCanonicalizing($expected, $records->jsonSerialize());
    }

}
