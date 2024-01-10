<?php

namespace SilverStripe\Search\Tests\Filter;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Search\Filter\Criteria;
use SilverStripe\Search\Filter\CriteriaAdaptor as CriteriaAdaptorInterface;

class CriteriaAdaptor implements TestOnly, CriteriaAdaptorInterface
{

    use Injectable;

    public function generateClauseString(Criteria $criterion): string
    {
        // TODO: Implement generateClauseString() method.
    }

}
