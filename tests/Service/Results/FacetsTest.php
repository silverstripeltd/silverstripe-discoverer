<?php

namespace SilverStripe\Discoverer\Tests\Service\Results;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Service\Results\Facet;
use SilverStripe\Discoverer\Service\Results\FacetData;
use SilverStripe\Discoverer\Service\Results\Facets;

class FacetsTest extends SapphireTest
{

    public function testJsonSerialize(): void
    {
        $facetDataOne = FacetData::create();
        $facetDataOne->setCount(1);
        $facetDataOne->setFrom(111);
        $facetDataOne->setTo(222);
        $facetDataOne->setName('test');
        $facetDataOne->setValue('testing');

        $facetOne = Facet::create();
        $facetOne->setName('test');
        $facetOne->setFieldName('testing');
        $facetOne->setType('testType');
        $facetOne->addData($facetDataOne);

        $facetDataTwo = FacetData::create();
        $facetDataTwo->setCount(2);
        $facetDataTwo->setFrom(222);
        $facetDataTwo->setTo(333);
        $facetDataTwo->setName('test2');
        $facetDataTwo->setValue('testing2');

        $facetTwo = Facet::create();
        $facetTwo->setName('test2');
        $facetTwo->setFieldName('testing2');
        $facetTwo->setType('testType2');
        $facetTwo->addData($facetDataTwo);

        $facets = Facets::create();
        $facets->add($facetOne);
        $facets->add($facetTwo);

        $expected = [
            [
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
                ],
            ],
            [
                'type' => 'testType2',
                'name' => 'test2',
                'value' => 'testing2',
                'data' => [
                    [
                        'count' => 2,
                        'from' => 222,
                        'to' => 333,
                        'name' => 'test2',
                        'value' => 'testing2',
                    ],
                ],
            ],
        ];

        $this->assertEqualsCanonicalizing($expected, $facets->jsonSerialize());
    }

    public function testJsonSerializeEmpty(): void
    {
        $facets = Facets::create();

        $this->assertEqualsCanonicalizing([], $facets->jsonSerialize());
    }

}
