<?php

namespace SilverStripe\Discoverer\Tests\Query\Facet;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Query\Facet\Facet;
use SilverStripe\Discoverer\Query\Facet\FacetAdaptor;
use SilverStripe\Discoverer\Query\Facet\FacetCollection;

class FacetCollectionTest extends SapphireTest
{

    public function testAddFacet(): void
    {
        $collection = FacetCollection::create();
        $facetOne = Facet::create();
        $facetTwo = Facet::create();
        $facetThree = Facet::create();

        $collection->addFacet($facetOne);
        $collection->addFacet($facetTwo);
        $collection->addFacet($facetThree);

        $this->assertCount(3, $collection->getFacets());
    }

    public function testPrepareFacets(): void
    {
        $facetCollection = FacetCollection::create();

        $facetOne = Facet::create();
        $facetOne->setType(Facet::TYPE_VALUE);
        $facetOne->setFieldName('fieldName1');
        $facetOne->setName('facetName1');
        $facetOne->setLimit(2);

        $facetTwo = Facet::create();
        $facetTwo->setType(Facet::TYPE_RANGE);
        $facetTwo->setFieldName('fieldName2');
        $facetTwo->setName('facetName2');
        $facetTwo->addRange(1, 2, 'rangeNameOne');
        $facetTwo->addRange(3, 4, 'rangeNameTwo');

        $facetThree = Facet::create();
        $facetThree->setType(Facet::TYPE_RANGE);
        $facetThree->setFieldName('fieldName3');
        $facetThree->setName('facetName3');

        $facetCollection->addFacet($facetOne);
        $facetCollection->addFacet($facetTwo);
        $facetCollection->addFacet($facetThree);

        $expected = [
            [
                'limit' => 2,
                'name' => 'facetName1',
                'property' => 'fieldName1',
                'type' => Facet::TYPE_VALUE,
                'ranges' => [],
            ],
            [
                'limit' => null,
                'name' => 'facetName2',
                'property' => 'fieldName2',
                'type' => Facet::TYPE_RANGE,
                'ranges' => [
                    [
                        'from' => 1,
                        'to' => 2,
                        'name' => 'rangeNameOne',
                    ],
                    [
                        'from' => 3,
                        'to' => 4,
                        'name' => 'rangeNameTwo',
                    ],
                ],
            ],
            [
                'limit' => null,
                'name' => 'facetName3',
                'property' => 'fieldName3',
                'type' => Facet::TYPE_RANGE,
                'ranges' => [],
            ],
        ];

        $this->assertEqualsCanonicalizing($expected, $facetCollection->getPreparedFacets());
    }

    protected function setUp(): void
    {
        parent::setUp();

        Injector::inst()->registerService(new MockFacetAdaptor(), FacetAdaptor::class);
    }

}
