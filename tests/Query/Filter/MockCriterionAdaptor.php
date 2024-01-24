<?php

namespace SilverStripe\Discoverer\Tests\Query\Filter;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Discoverer\Query\Filter\Criterion;
use SilverStripe\Discoverer\Query\Filter\CriterionAdaptor;

class MockCriterionAdaptor implements TestOnly, CriterionAdaptor
{

    public function prepareCriterion(Criterion $criterion): string
    {
        return sprintf('%s-%s-%s', $criterion->getTarget(), $criterion->getValue(), $criterion->getComparison());
    }

}
