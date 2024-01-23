<?php

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Search\Analytics\AnalyticsData;
use SilverStripe\Search\Service\Results\Field;
use SilverStripe\Search\Service\Results\Record;

class RecordTest extends SapphireTest
{

    public function testAnalyticsData(): void
    {
        $record = Record::create();
        $record->setAnalyticsData(AnalyticsData::create());

        $this->assertInstanceOf(AnalyticsData::class, $record->getAnalyticsData());
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
        $this->expectExceptionMessage(
            'Field value must be an instance of SilverStripe\\Search\\Service\\Result\\Field'
        );

        $record = Record::create();
        // This should throw our Exception
        $record->Title = 'Invalid';
    }

}
