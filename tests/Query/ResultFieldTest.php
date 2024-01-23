<?php

namespace SilverStripe\Search\Tests\Query;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Search\Query\ResultField;

class ResultFieldTest extends SapphireTest
{

    public function testConstruct(): void
    {
        $resultFieldOne = ResultField::create('fieldName1');
        $resultFieldTwo = ResultField::create('fieldName2', 100);
        $resultFieldThree = ResultField::create('fieldName3', 200, true);

        $this->assertEquals('fieldName1', $resultFieldOne->getFieldName());
        $this->assertEquals(0, $resultFieldOne->getLength());
        $this->assertFalse($resultFieldOne->isFormatted());

        $this->assertEquals('fieldName2', $resultFieldTwo->getFieldName());
        $this->assertEquals(100, $resultFieldTwo->getLength());
        $this->assertFalse($resultFieldTwo->isFormatted());

        $this->assertEquals('fieldName3', $resultFieldThree->getFieldName());
        $this->assertEquals(200, $resultFieldThree->getLength());
        $this->assertTrue($resultFieldThree->isFormatted());
    }

}
