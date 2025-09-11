<?php

namespace SilverStripe\Discoverer\Tests\Service\Results;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Service\Results\FacetData;

class FacetDataTest extends SapphireTest
{

    public function testJsonSerialize(): void
    {
        $facetData = FacetData::create();
        $facetData->setCount(1);
        $facetData->setFrom(111);
        $facetData->setTo(222);
        $facetData->setName('test');
        $facetData->setValue('testing');

        $expected = [
            'count' => 1,
            'from' => 111,
            'to' => 222,
            'name' => 'test',
            'value' => 'testing',
        ];

        $this->assertEqualsCanonicalizing($expected, $facetData->jsonSerialize());
    }

    public function testJsonSerializeEmpty(): void
    {
        $expected = [
            'count' => null,
            'from' => null,
            'to' => null,
            'name' => null,
            'value' => null,
        ];

        $facetData = FacetData::create();

        $this->assertEqualsCanonicalizing($expected, $facetData->jsonSerialize());
    }

}
