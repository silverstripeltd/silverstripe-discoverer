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

}
