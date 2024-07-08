<?php

namespace SilverStripe\Discoverer\Tests\Service\Results;

use ArrayIterator;
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
        $fieldTwo = Field::create([1, 2, 3, 4]);
        $fieldThree = Field::create();
        $fieldFour = Field::create([]);

        /** @var ArrayIterator|Field[] $fieldOneIterator */
        $fieldOneIterator = $fieldOne->getIterator();
        /** @var ArrayIterator|Field[] $fieldTwoIterator */
        $fieldTwoIterator = $fieldTwo->getIterator();
        /** @var ArrayIterator|Field[] $fieldThreeIterator */
        $fieldThreeIterator = $fieldThree->getIterator();
        /** @var ArrayIterator|Field[] $fieldFourIterator */
        $fieldFourIterator = $fieldFour->getIterator();

        $this->assertCount(1, $fieldOneIterator);
        $this->assertCount(4, $fieldTwoIterator);
        $this->assertCount(0, $fieldThreeIterator);
        $this->assertCount(0, $fieldFourIterator);

        $this->assertEquals('raw', $fieldOneIterator->current()->getRaw());

        $expected = [1, 2, 3, 4];
        $results = [];

        foreach ($fieldTwoIterator as $field) {
            $this->assertInstanceOf(Field::class, $field);

            $results[] = $field->getRaw();
        }

        $this->assertEqualsCanonicalizing($expected, $results);
    }

}
