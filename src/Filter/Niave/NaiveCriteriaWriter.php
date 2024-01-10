<?php

namespace SilverStripe\Search\Filter\Niave;

use SilverStripe\Search\Filter\Criteria;
use SilverStripe\Search\Filter\CriteriaWriter;

class NaiveCriteriaWriter implements CriteriaWriter
{

    public function generateClauseString(Criteria $criterion): string
    {
        return '';
    }

}
