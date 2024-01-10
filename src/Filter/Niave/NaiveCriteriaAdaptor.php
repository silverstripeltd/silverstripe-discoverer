<?php

namespace SilverStripe\Search\Filter\Niave;

use SilverStripe\Search\Filter\Criteria;
use SilverStripe\Search\Filter\CriteriaAdaptor;

class NaiveCriteriaAdaptor implements CriteriaAdaptor
{

    public function generateClauseString(Criteria $criterion): string
    {
        return 'naive';
    }

}
