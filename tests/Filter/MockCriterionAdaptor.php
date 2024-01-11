<?php

namespace SilverStripe\Search\Tests\Filter;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Search\Filter\Criterion;
use SilverStripe\Search\Filter\CriterionAdaptor;

class MockCriterionAdaptor implements TestOnly, CriterionAdaptor
{

    public function prepareClause(Criterion $criterion): string
    {
        return sprintf('%s-%s-%s', $criterion->getTarget(), $criterion->getValue(), $criterion->getComparison());
    }

}
