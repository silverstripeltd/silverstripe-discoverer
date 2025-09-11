<?php

namespace SilverStripe\Discoverer\Tests\Service\Results;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Query\Facet\FacetAdaptor;
use SilverStripe\Discoverer\Query\Filter\CriteriaAdaptor;
use SilverStripe\Discoverer\Query\Filter\CriterionAdaptor;
use SilverStripe\Discoverer\Query\Query;
use SilverStripe\Discoverer\Service\Results\Facet;
use SilverStripe\Discoverer\Service\Results\Field;
use SilverStripe\Discoverer\Service\Results\Record;
use SilverStripe\Discoverer\Service\Results\Results;
use SilverStripe\Discoverer\Tests\Query\Facet\MockFacetAdaptor;
use SilverStripe\Discoverer\Tests\Query\Filter\MockCriteriaAdaptor;
use SilverStripe\Discoverer\Tests\Query\Filter\MockCriterionAdaptor;

class ResultsTest extends SapphireTest
{

    public function testJsonSerialize(): void
    {
        $recordOne = Record::create();
        $recordOne->CustomField = Field::create('raw', 'formatted');

        $recordTwo = Record::create();
        $recordTwo->CustomField = Field::create('raw2', 'formatted2');

        $facetOne = Facet::create();
        $facetOne->setName('test');
        $facetOne->setFieldName('testing');
        $facetOne->setType('testType');

        $facetTwo = Facet::create();
        $facetTwo->setName('test2');
        $facetTwo->setFieldName('testing2');
        $facetTwo->setType('testType2');

        $results = Results::create(200, Query::create('test'));
        $results->addRecord($recordOne);
        $results->addRecord($recordTwo);
        $results->addFacet($facetOne);
        $results->addFacet($facetTwo);

        $expected = [
            'records' => [
                [
                    'AnalyticsData' => null,
                    'CustomFieldText' => [
                        'raw' => 'raw',
                        'formatted' => 'formatted',
                    ],
                ],
                [
                    'AnalyticsData' => null,
                    'CustomFieldText' => [
                        'raw' => 'raw2',
                        'formatted' => 'formatted2',
                    ],
                ],
            ],
            'facets' => [
                [
                    'type' => 'testType',
                    'name' => 'test',
                    'value' => 'testing',
                    'data' => [],
                ],
                [
                    'type' => 'testType2',
                    'name' => 'test2',
                    'value' => 'testing2',
                    'data' => [],
                ],
            ],
        ];

        $this->assertEqualsCanonicalizing($expected, $results->jsonSerialize());
    }

    public function testJsonSerializeEmpty(): void
    {
        $results = Results::create(200, Query::create('test'));

        $expected = [
            'records' => [],
            'facets' => [],
        ];

        $this->assertEqualsCanonicalizing($expected, $results->jsonSerialize());
    }

    protected function setUp(): void
    {
        parent::setUp();

        Injector::inst()->registerService(new MockCriteriaAdaptor(), CriteriaAdaptor::class);
        Injector::inst()->registerService(new MockCriterionAdaptor(), CriterionAdaptor::class);
        Injector::inst()->registerService(new MockFacetAdaptor(), FacetAdaptor::class);
    }

}
