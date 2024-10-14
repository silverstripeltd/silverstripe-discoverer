<?php

namespace SilverStripe\Discoverer\Tests\Service;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Service\FieldService;

class FieldServiceTest extends SapphireTest
{

    /**
     * @dataProvider provideFieldNames
     */
    public function testGetConvertedFieldName(string $fieldName, string $expectedFieldName): void
    {
        $this->assertEquals(FieldService::singleton()->getConvertedFieldName($fieldName), $expectedFieldName);
    }

    public function provideFieldNames(): array
    {
        return [
            ['title', 'Title'],
            ['Title', 'Title'],
            ['elemental_area', 'ElementalArea'],
            ['Elemental_Area', 'ElementalArea'],
            ['elemental-area', 'ElementalArea'],
            ['Elemental-Area', 'ElementalArea'],
            ['elementalArea', 'ElementalArea'],
            ['ElementalArea', 'ElementalArea'],
            ['record_id', 'RecordId'],
            ['Record_Id', 'RecordId'],
            ['record-id', 'RecordId'],
            ['Record-Id', 'RecordId'],
            ['recordId', 'RecordId'],
            ['RecordId', 'RecordId'],
            ['RecordID', 'RecordId'],
            ['recordID', 'RecordId'],
            ['tag_ids', 'TagIds'],
            ['tag-ids', 'TagIds'],
            ['tagIds', 'TagIds'],
            ['TagIds', 'TagIds'],
            ['TagIDs', 'TagIds'],
            ['tagIDs', 'TagIds'],
            ['IDs', 'Ids'],
        ];
    }

}
