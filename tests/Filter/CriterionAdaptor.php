<?php

namespace SilverStripe\Search\Tests\Filter;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Search\Filter\Criterion;
use SilverStripe\Search\Filter\CriterionAdaptor as CriterionAdaptorInterface;

class CriterionAdaptor implements TestOnly, CriterionAdaptorInterface
{

    use Injectable;

    public function prepareClause(Criterion $criterion): string
    {
        return sprintf('%s-%s-%s', $criterion->getTarget(), $criterion->getValue(), $criterion->getComparison());
    }

}
