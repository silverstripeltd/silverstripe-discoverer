<?php

namespace SilverStripe\Discoverer\Tests\Service\Results;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Service\Results\Field;

class FieldTest extends SapphireTest
{

    public function testConstruct(): void
    {
        $fieldOne = Field::create('raw');
        $fieldTwo = Field::create('raw', 'snippet');

        $this->assertEquals('raw', $fieldOne->getRaw());
        $this->assertNull($fieldOne->getFormatted());

        $this->assertEquals('raw', $fieldTwo->getRaw());
        $this->assertEquals('snippet', $fieldTwo->getFormatted());
    }

    public function testForTemplate(): void
    {
        $fieldOne = Field::create('raw');
        $fieldTwo = Field::create('raw', 'snippet');

        $this->assertEquals('raw', $fieldOne->forTemplate());
        $this->assertEquals('snippet', $fieldTwo->forTemplate());
    }

    public function testGetIterator(): void
    {
        $fieldOne = Field::create('raw');
        $fieldTwo = Field::create();
        $fieldThree = Field::create([1, 2, 3, 4]);

        /** @var Field[] $fieldThreeIterator */
        $fieldThreeIterator = $fieldThree->getIterator();

        $this->assertEquals(['raw'], (array) $fieldOne->getIterator());
        $this->assertEquals([null], (array) $fieldTwo->getIterator());
        $this->assertCount(4, $fieldThreeIterator);

        $expected = [1, 2, 3, 4];
        $results = [];

        foreach ($fieldThreeIterator as $field) {
            $this->assertInstanceOf(Field::class, $field);

            $results[] = $field->getRaw();
        }

        $this->assertEqualsCanonicalizing($expected, $results);
    }

}
