<?php

namespace SilverStripe\Search\Tests\Query\Filter;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Search\Query\Filter\Criterion;
use SilverStripe\Search\Query\Filter\CriterionAdaptor;

class MockCriterionAdaptor implements TestOnly, CriterionAdaptor
{

    public function prepareCriterion(Criterion $criterion): string
    {
        return sprintf('%s-%s-%s', $criterion->getTarget(), $criterion->getValue(), $criterion->getComparison());
    }

}
