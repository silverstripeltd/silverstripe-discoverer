<?php

namespace SilverStripe\Search\Tests\Query;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Search\Query\Filter\Criteria;
use SilverStripe\Search\Query\Filter\CriteriaAdaptor;
use SilverStripe\Search\Query\Filter\Criterion;
use SilverStripe\Search\Query\Filter\CriterionAdaptor;
use SilverStripe\Search\Query\Query;
use SilverStripe\Search\Query\ResultField;
use SilverStripe\Search\Tests\Query\Filter\MockCriteriaAdaptor;
use SilverStripe\Search\Tests\Query\Filter\MockCriterionAdaptor;

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
        $query->addResultField('field1');
        $query->addResultField('field2', 100);
        $query->addResultField('field3', 0, true);
        $query->addResultField('field4', 100, true);

        $resultFields = $query->getResultFields();

        $this->assertCount(4, $resultFields);

        /** @var ResultField $resultFieldOne */
        $resultFieldOne = array_shift($resultFields);
        /** @var ResultField $resultFieldTwo */
        $resultFieldTwo = array_shift($resultFields);
        /** @var ResultField $resultFieldThree */
        $resultFieldThree = array_shift($resultFields);
        /** @var ResultField $resultFieldFour */
        $resultFieldFour = array_shift($resultFields);

        $this->assertEquals('field1', $resultFieldOne->getFieldName());
        $this->assertEquals(0, $resultFieldOne->getLength());
        $this->assertFalse($resultFieldOne->isFormatted());
        $this->assertEquals('field2', $resultFieldTwo->getFieldName());
        $this->assertEquals(100, $resultFieldTwo->getLength());
        $this->assertFalse($resultFieldTwo->isFormatted());
        $this->assertEquals('field3', $resultFieldThree->getFieldName());
        $this->assertEquals(0, $resultFieldThree->getLength());
        $this->assertTrue($resultFieldThree->isFormatted());
        $this->assertEquals('field4', $resultFieldFour->getFieldName());
        $this->assertEquals(100, $resultFieldFour->getLength());
        $this->assertTrue($resultFieldFour->isFormatted());
    }

    public function testResultFields(): void
    {
        $query = Query::create();
        // Can mix/match associative and numeric
        $query->addResultFields([
            ['field1'],
            ['field2', 0],
            ['field3', 100],
            ['field4', 0, true],
            ['field5', 100, true],
        ]);

        $resultFields = $query->getResultFields();

        $this->assertCount(5, $resultFields);

        /** @var ResultField $resultFieldOne */
        $resultFieldOne = array_shift($resultFields);
        /** @var ResultField $resultFieldTwo */
        $resultFieldTwo = array_shift($resultFields);
        /** @var ResultField $resultFieldThree */
        $resultFieldThree = array_shift($resultFields);
        /** @var ResultField $resultFieldFour */
        $resultFieldFour = array_shift($resultFields);
        /** @var ResultField $resultFieldFive */
        $resultFieldFive = array_shift($resultFields);

        $this->assertEquals('field1', $resultFieldOne->getFieldName());
        $this->assertEquals(0, $resultFieldOne->getLength());
        $this->assertFalse($resultFieldOne->isFormatted());
        $this->assertEquals('field2', $resultFieldTwo->getFieldName());
        $this->assertEquals(0, $resultFieldTwo->getLength());
        $this->assertFalse($resultFieldTwo->isFormatted());
        $this->assertEquals('field3', $resultFieldThree->getFieldName());
        $this->assertEquals(100, $resultFieldThree->getLength());
        $this->assertFalse($resultFieldThree->isFormatted());
        $this->assertEquals('field4', $resultFieldFour->getFieldName());
        $this->assertEquals(0, $resultFieldFour->getLength());
        $this->assertTrue($resultFieldFour->isFormatted());
        $this->assertEquals('field5', $resultFieldFive->getFieldName());
        $this->assertEquals(100, $resultFieldFive->getLength());
        $this->assertTrue($resultFieldFive->isFormatted());
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

    public function testFilterWithClause(): void
    {
        $query = Query::create();
        $criterionOne = Criterion::create('field1', 'value1', Criterion::EQUAL);
        $criterionTwo = Criterion::create('field2', 'value2', Criterion::EQUAL);

        $query->filter($criterionOne);
        $query->filter($criterionTwo);

        $clauses = $query->getFilter()->getClauses();

        $this->assertCount(2, $clauses);

        /** @var Criterion $criterionOne */
        $criterionOne = array_shift($clauses);
        /** @var Criterion $criterionTwo */
        $criterionTwo = array_shift($clauses);

        $this->assertEquals('field1', $criterionOne->getTarget());
        $this->assertEquals('value1', $criterionOne->getValue());
        $this->assertEquals(Criterion::EQUAL, $criterionOne->getComparison());
        $this->assertEquals('field2', $criterionTwo->getTarget());
        $this->assertEquals('value2', $criterionTwo->getValue());
        $this->assertEquals(Criterion::EQUAL, $criterionTwo->getComparison());
    }

    public function testFilterWithTarget(): void
    {
        $query = Query::create();
        $query->filter('field1', 'value1', Criterion::EQUAL);
        $query->filter('field2', 'value2', Criterion::EQUAL);

        $clauses = $query->getFilter()->getClauses();

        $this->assertCount(2, $clauses);

        /** @var Criterion $criterionOne */
        $criterionOne = array_shift($clauses);
        /** @var Criterion $criterionTwo */
        $criterionTwo = array_shift($clauses);

        $this->assertEquals('field1', $criterionOne->getTarget());
        $this->assertEquals('value1', $criterionOne->getValue());
        $this->assertEquals(Criterion::EQUAL, $criterionOne->getComparison());
        $this->assertEquals('field2', $criterionTwo->getTarget());
        $this->assertEquals('value2', $criterionTwo->getValue());
        $this->assertEquals(Criterion::EQUAL, $criterionTwo->getComparison());
    }

    public function testFilterAny(): void
    {
        $query = Query::create();
        $criterion = Criterion::create('field1', 'value1', Criterion::EQUAL);

        $query->filterAny([
            $criterion,
            ['field2', 'value2', Criterion::EQUAL],
            ['field3', 'value3', Criterion::EQUAL],
        ]);

        $clauses = $query->getFilter()->getClauses();

        // A filterAny() creates a new Criteria to contain all the clauses with an OR conjunction, so even though there
        // are 3 filters above, this should be within a single clause in our Query's filter
        $this->assertCount(1, $clauses);

        /** @var Criteria $criteria */
        $criteria = array_shift($clauses);

        // Make sure it is a Criteria (a collection of our 3 filters)
        $this->assertInstanceOf(Criteria::class, $criteria);

        $clauses = $criteria->getClauses();
        /** @var Criterion $criterionOne */
        $criterionOne = array_shift($clauses);
        /** @var Criterion $criterionTwo */
        $criterionTwo = array_shift($clauses);
        /** @var Criterion $criterionThree */
        $criterionThree = array_shift($clauses);

        $this->assertEquals('field1', $criterionOne->getTarget());
        $this->assertEquals('value1', $criterionOne->getValue());
        $this->assertEquals(Criterion::EQUAL, $criterionOne->getComparison());
        $this->assertEquals('field2', $criterionTwo->getTarget());
        $this->assertEquals('value2', $criterionTwo->getValue());
        $this->assertEquals(Criterion::EQUAL, $criterionTwo->getComparison());
        $this->assertEquals('field3', $criterionThree->getTarget());
        $this->assertEquals('value3', $criterionThree->getValue());
        $this->assertEquals(Criterion::EQUAL, $criterionThree->getComparison());
    }

    public function testFilterNoValue(): void
    {
        $this->expectExceptionMessage('mixed $value and string $comparison expected for filter()');

        $query = Query::create();
        // Should throw our exception
        $query->filter('field1');
    }

    public function testFilterNoComparison(): void
    {
        $this->expectExceptionMessage('string $comparison expected for filter()');

        $query = Query::create();
        // Should throw our exception
        $query->filter('field1', 'value1');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Injector::inst()->registerService(new MockCriteriaAdaptor(), CriteriaAdaptor::class);
        Injector::inst()->registerService(new MockCriterionAdaptor(), CriterionAdaptor::class);
    }

}
