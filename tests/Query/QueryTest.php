<?php

namespace SilverStripe\Search\Tests\Query;

use ReflectionProperty;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Search\Filter\Criteria;
use SilverStripe\Search\Filter\CriteriaAdaptor;
use SilverStripe\Search\Filter\CriterionAdaptor;
use SilverStripe\Search\Query\Query;
use SilverStripe\Search\Tests\Filter\MockCriteriaAdaptor;
use SilverStripe\Search\Tests\Filter\MockCriterionAdaptor;

class QueryTest extends SapphireTest
{

    public function testDefaultFilterConjunction(): void
    {
        $query = Query::create();

        $this->assertEquals(Criteria::CONJUNCTION_AND, $query->getFilter()->getConjunction());
    }

    public function testQueryString(): void
    {
        $query = Query::create('initial query string');

        $this->assertEquals('initial query string', $query->getQueryString());

        $query->setQueryString('modified query string');

        $this->assertEquals('modified query string', $query->getQueryString());
    }

    public function testAddSort(): void
    {
        $query = Query::create();
        // Testing default direction of ASC
        $query->addSort('field1');
        // Testing specified directin of DESC
        $query->addSort('field2', Query::SORT_DESC);

        $expected = [
            'field1' => Query::SORT_ASC,
            'field2' => Query::SORT_DESC,
        ];

        $this->assertEquals($expected, $query->getSort());

        // Adding a sort for an existing field should override
        $query->addSort('field1', Query::SORT_DESC);

        $expected = [
            'field1' => Query::SORT_DESC,
            'field2' => Query::SORT_DESC,
        ];

        $this->assertEquals($expected, $query->getSort());
    }

    public function testAddSorts(): void
    {
        $query = Query::create();
        $expected = [
            'field1' => Query::SORT_ASC,
            'field2' => Query::SORT_DESC,
        ];
        $query->addSorts($expected);

        $this->assertEquals($expected, $query->getSort());

        // Adding sorts for existing fields should override
        $expected = [
            'field1' => Query::SORT_DESC,
            'field2' => Query::SORT_ASC,
        ];
        $query->addSorts($expected);

        $this->assertEquals($expected, $query->getSort());
    }

    public function testAddResultField(): void
    {
        $query = Query::create();
        // No length specified
        $query->addResultField('field1');
        // Length specified
        $query->addResultField('field2', 100);
        // Length of 0 specified (should be treated as no length limit)
        $query->addResultField('field3', 0);

        $expected = [
            'field1' => null,
            'field2' => 100,
            'field3' => null,
        ];

        $this->assertEquals($expected, $query->getResultFields());
    }

    public function testResultFields(): void
    {
        $query = Query::create();
        // Can mix/match associative and numeric
        $query->addResultFields([
            'field1' => 0,
            'field2' => 100,
            'field3' => 200,
            'field4',
        ]);

        $expected = [
            'field1' => null,
            'field2' => 100,
            'field3' => 200,
            'field4' => null,
        ];

        $this->assertEquals($expected, $query->getResultFields());
    }

    public function testAddSearchField(): void
    {
        $query = Query::create();
        // No weight specified
        $query->addSearchField('field1');
        // Weight specified
        $query->addSearchField('field2', 2);
        // Weight of 0 specified (should be treated as no weight applied)
        $query->addSearchField('field3', 0);

        $expected = [
            'field1' => null,
            'field2' => 2,
            'field3' => null,
        ];

        $this->assertEquals($expected, $query->getSearchFields());
    }

    public function testSearchFields(): void
    {
        $query = Query::create();
        // Can mix/match associative and numeric
        $query->addSearchFields([
            'field1' => 0,
            'field2' => 1,
            'field3' => 2,
            'field4',
        ]);

        $expected = [
            'field1' => null,
            'field2' => 1,
            'field3' => 2,
            'field4' => null,
        ];

        $this->assertEquals($expected, $query->getSearchFields());
    }

    public function testSetPageNum(): void
    {
        $query = Query::create();

        $this->assertNull($query->getPageNum());

        $query->setPageNum(1);

        $this->assertEquals(1, $query->getPageNum());
    }

    public function testSetPageSize(): void
    {
        $query = Query::create();

        $this->assertNull($query->getPageSize());

        $query->setPageSize(10);

        $this->assertEquals(10, $query->getPageSize());
    }

    public function testSetPagination(): void
    {
        $query = Query::create();

        $this->assertNull($query->getPageNum());
        $this->assertNull($query->getPageSize());

        $query->setPagination(10, 1);

        $this->assertEquals(1, $query->getPageNum());
        $this->assertEquals(10, $query->getPageSize());
    }

    protected function setUp(): void
    {
        parent::setUp();

        Injector::inst()->registerService(new MockCriteriaAdaptor(), CriteriaAdaptor::class);
        Injector::inst()->registerService(new MockCriterionAdaptor(), CriterionAdaptor::class);
    }

}
