<?php

namespace SilverStripe\Search\Filter\Niave;

use SilverStripe\Search\Filter\Criterion;
use SilverStripe\Search\Filter\CriterionAdaptor;

class NaiveCriterionAdaptor implements CriterionAdaptor
{

    public function prepareClause(Criterion $criterion): string
    {
        return 'naive';
    }

}
