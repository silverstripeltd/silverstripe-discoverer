<?php

namespace SilverStripe\Discoverer\Tests\Service\Results;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Service\Results\Facet;
use SilverStripe\Discoverer\Service\Results\FacetData;

class FacetTest extends SapphireTest
{

    public function testJsonSerialize(): void
    {
        $facetDataOne = FacetData::create();
        $facetDataOne->setCount(1);
        $facetDataOne->setFrom(111);
        $facetDataOne->setTo(222);
        $facetDataOne->setName('test');
        $facetDataOne->setValue('testing');

        $facetDataTwo = FacetData::create();
        $facetDataTwo->setCount(2);
        $facetDataTwo->setFrom(222);
        $facetDataTwo->setTo(333);
        $facetDataTwo->setName('test2');
        $facetDataTwo->setValue('testing2');

        $facet = Facet::create();
        $facet->setName('test');
        $facet->setFieldName('testing');
        $facet->setType('testType');
        $facet->addData($facetDataOne);
        $facet->addData($facetDataTwo);

        $expected = [
            'type' => 'testType',
            'name' => 'test',
            'value' => 'testing',
            'data' => [
                [
                    'count' => 1,
                    'from' => 111,
                    'to' => 222,
                    'name' => 'test',
                    'value' => 'testing',
                ],
                [
                    'count' => 2,
                    'from' => 222,
                    'to' => 333,
                    'name' => 'test2',
                    'value' => 'testing2',
                ],
            ],
        ];

        $this->assertEqualsCanonicalizing($expected, $facet->jsonSerialize());
    }

    public function testJsonSerializeEmpty(): void
    {
        $facet = Facet::create();

        $expected = [
            'data' => [],
            'type' => null,
            'name' => null,
            'value' => null,
        ];

        $this->assertEqualsCanonicalizing($expected, $facet->jsonSerialize());
    }

}
