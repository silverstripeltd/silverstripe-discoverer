<?php

namespace SilverStripe\Discoverer\Tests\Query\Filter;

use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Query\Filter\Criteria;
use SilverStripe\Discoverer\Query\Filter\CriteriaAdaptor;
use SilverStripe\Discoverer\Query\Filter\Criterion;
use SilverStripe\Discoverer\Query\Filter\CriterionAdaptor;

class CriteriaTest extends SapphireTest
{

    #[DataProvider('provideValidConjunctions')]
    public function testValidConjunctions(string $conjunction): void
    {
        $criteria = Criteria::create($conjunction);

        $this->assertEquals($conjunction, $criteria->getConjunction());
    }

    public static function provideValidConjunctions(): array
    {
        $data = [];

        foreach (Criteria::CONJUNCTIONS as $conjunction) {
            $data[] = [$conjunction];
        }

        return $data;
    }

    public function testInvalidConjunctions(): void
    {
        $this->expectExceptionMessage('Invalid conjunction provided "INVALID"');

        // Should throw our Exception
        Criteria::create('INVALID');
    }

    public function testCreateAll(): void
    {
        $criteria = Criteria::createAll();

        $this->assertEquals(Criteria::CONJUNCTION_AND, $criteria->getConjunction());
    }

    public function testCreateAny(): void
    {
        $criteria = Criteria::createAny();

        $this->assertEquals(Criteria::CONJUNCTION_OR, $criteria->getConjunction());
    }

    public function testAddCriteria(): void
    {
        $criteria = Criteria::create(Criteria::CONJUNCTION_OR);
        $childCriteria = Criteria::create(Criteria::CONJUNCTION_AND);

        $criteria->addClause($childCriteria);

        $this->assertCount(1, $criteria->getClauses());
    }

    public function testAddCriterion(): void
    {
        $criteria = Criteria::create(Criteria::CONJUNCTION_OR);
        $childCriterion = Criterion::create('fieldName', 'fieldValue', Criterion::EQUAL);

        $criteria->addClause($childCriterion);

        $this->assertCount(1, $criteria->getClauses());
    }

    public function testBasicOrConjunction(): void
    {
        $criteria = Criteria::create(Criteria::CONJUNCTION_OR);
        $criterionOne = Criterion::create('field1', 'value1', Criterion::EQUAL);
        $criterionTwo = Criterion::create('field2', 'value2', Criterion::EQUAL);
        $criterionThree = Criterion::create('field3', 'value3', Criterion::EQUAL);

        $criteria->addClause($criterionOne);
        $criteria->addClause($criterionTwo);
        $criteria->addClause($criterionThree);

        $expected = '(field1-value1-EQUAL OR field2-value2-EQUAL OR field3-value3-EQUAL)';

        $this->assertEquals($expected, $criteria->getPreparedClause());
    }

    public function testBasicAndConjunction(): void
    {
        $criteria = Criteria::create(Criteria::CONJUNCTION_AND);
        $criterionOne = Criterion::create('field1', 'value1', Criterion::EQUAL);
        $criterionTwo = Criterion::create('field2', 'value2', Criterion::EQUAL);
        $criterionThree = Criterion::create('field3', 'value3', Criterion::EQUAL);

        $criteria->addClause($criterionOne);
        $criteria->addClause($criterionTwo);
        $criteria->addClause($criterionThree);

        $expected = '(field1-value1-EQUAL AND field2-value2-EQUAL AND field3-value3-EQUAL)';

        $this->assertEquals($expected, $criteria->getPreparedClause());
    }

    public function testNestedClauses(): void
    {
        $criteriaParent = Criteria::create(Criteria::CONJUNCTION_AND);

        $criteriaAnd = Criteria::create(Criteria::CONJUNCTION_AND);
        $criterionOne = Criterion::create('field1', 'value1', Criterion::EQUAL);
        $criterionTwo = Criterion::create('field2', 'value2', Criterion::EQUAL);
        $criterionThree = Criterion::create('field3', 'value3', Criterion::EQUAL);

        $criteriaAnd->addClause($criterionOne);
        $criteriaAnd->addClause($criterionTwo);
        $criteriaAnd->addClause($criterionThree);

        $criteriaOr = Criteria::create(Criteria::CONJUNCTION_OR);
        $criterionOne = Criterion::create('field1', 'value1', Criterion::EQUAL);
        $criterionTwo = Criterion::create('field2', 'value2', Criterion::EQUAL);
        $criterionThree = Criterion::create('field3', 'value3', Criterion::EQUAL);

        $criteriaOr->addClause($criterionOne);
        $criteriaOr->addClause($criterionTwo);
        $criteriaOr->addClause($criterionThree);

        $criteriaParent->addClause($criteriaAnd);
        $criteriaParent->addClause($criteriaOr);

        $expectedAndStatement = 'field1-value1-EQUAL AND field2-value2-EQUAL AND field3-value3-EQUAL';
        $expectedOrStatement = 'field1-value1-EQUAL OR field2-value2-EQUAL OR field3-value3-EQUAL';
        $expectedCombined = sprintf('((%s) AND (%s))', $expectedAndStatement, $expectedOrStatement);

        $this->assertEquals($expectedCombined, $criteriaParent->getPreparedClause());
    }

    public function testFilterWithClause(): void
    {
        $criteria = Criteria::createAny();
        $criterionOne = Criterion::create('field1', 'value1', Criterion::EQUAL);
        $criterionTwo = Criterion::create('field2', 'value2', Criterion::EQUAL);

        $criteria->filter($criterionOne);
        $criteria->filter($criterionTwo);

        $clauses = $criteria->getClauses();

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
        $criteria = Criteria::createAny();
        $criteria->filter('field1', 'value1', Criterion::EQUAL);
        $criteria->filter('field2', 'value2', Criterion::EQUAL);

        $clauses = $criteria->getClauses();

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

    protected function setUp(): void
    {
        parent::setUp();

        Injector::inst()->registerService(new MockCriteriaAdaptor(), CriteriaAdaptor::class);
        Injector::inst()->registerService(new MockCriterionAdaptor(), CriterionAdaptor::class);
    }

}
