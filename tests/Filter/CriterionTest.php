<?php

namespace SilverStripe\Search\Tests\Filter;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Search\Filter\Criterion;
use SilverStripe\Search\Filter\CriterionAdaptor as CriterionAdaptorInterface;

class CriterionTest extends SapphireTest
{

    /**
     * A basic test to make sure that the dependency injection works for the clause writer
     */
    public function testGetPreparedClause(): void
    {
        $criterion = Criterion::create('fieldName', 'fieldValue', Criterion::EQUAL);
        $expected = sprintf('%s-%s-%s', 'fieldName', 'fieldValue', Criterion::EQUAL);

        $this->assertEquals($expected, $criterion->getPreparedClause());
    }

    protected function setUp(): void
    {
        parent::setUp();

        Injector::inst()->registerService(new CriterionAdaptor(), CriterionAdaptorInterface::class);
    }

}
