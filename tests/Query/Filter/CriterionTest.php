<?php

namespace SilverStripe\Discoverer\Tests\Query\Filter;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Query\Filter\Criterion;
use SilverStripe\Discoverer\Query\Filter\CriterionAdaptor;

class CriterionTest extends SapphireTest
{

    /**
     * @dataProvider provideValidComparisons
     */
    public function testValidComparisons(string $comparison): void
    {
        $value = 'fieldValue';

        if ($comparison === Criterion::RANGE) {
            $value = [
                'from' => 1,
                'to' => 2,
            ];
        } elseif ($comparison === Criterion::IN || $comparison === Criterion::NOT_IN) {
            $value = [
                1,
                2,
            ];
        }

        $criterion = Criterion::create('fieldName', $value, $comparison);

        $this->assertEquals('fieldName', $criterion->getTarget());
        $this->assertEquals($value, $criterion->getValue());
        $this->assertEquals($comparison, $criterion->getComparison());
    }

    public function provideValidComparisons(): array
    {
        $data = [];

        foreach (Criterion::COMPARISONS as $comparison) {
            $data[] = [$comparison];
        }

        return $data;
    }

    public function testInvalidComparison(): void
    {
        $this->expectExceptionMessage('Invalid comparison provided: "INVALID"');

        // Should throw our Exception
        Criterion::create('fieldName', 'fieldValue', 'INVALID');
    }

    /**
     * A basic test to make sure that the dependency injection works for the clause writer
     */
    public function testGetPreparedClause(): void
    {
        $criterion = Criterion::create('fieldName', 'fieldValue', Criterion::EQUAL);
        $expected = sprintf('%s-%s-%s', 'fieldName', 'fieldValue', Criterion::EQUAL);

        $this->assertEquals($expected, $criterion->getPreparedClause());
    }

    public function testInvalidRangeValueComparison(): void
    {
        $this->expectExceptionMessage('Expected to receive an array with "from" and "to" keys for Range $value');

        // Should throw our Exception
        Criterion::create('fieldName', 'not an array', Criterion::RANGE);
    }

    public function testInvalidRangeKeysComparison(): void
    {
        $this->expectExceptionMessage('Range comparison $value must contain one (or both) of keys "from" and "to"');

        $range = [
            'notFrom' => 1,
            'notTo' => 2,
        ];

        // Should throw our Exception
        Criterion::create('fieldName', $range, Criterion::RANGE);
    }

    /**
     * @dataProvider provideInComparisons
     */
    public function testInvalidInValueComparison(): void
    {
        $this->expectExceptionMessage('$value of type array expected for IN and NOT_IN comparisons');

        // Should throw our Exception
        Criterion::create('fieldName', 'not an array', Criterion::IN);
    }

    public function provideInComparisons(): array
    {
        return [
            [Criterion::IN],
            [Criterion::NOT_IN],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Injector::inst()->registerService(new MockCriterionAdaptor(), CriterionAdaptor::class);
    }

}
