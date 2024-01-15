<?php

namespace SilverStripe\Search\Query\Filter;

interface CriterionAdaptor
{

    public function prepareCriterion(Criterion $criterion): mixed;

}
