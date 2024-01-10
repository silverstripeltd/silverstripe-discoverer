<?php

namespace SilverStripe\Search\Filter\Niave;

use SilverStripe\Search\Filter\Criterion;
use SilverStripe\Search\Filter\CriterionWriter;

class NaiveCriterionWriter implements CriterionWriter
{

    public function generateClauseString(Criterion $criterion): string
    {
        return '';
    }

}
