<?php

namespace SilverStripe\Discoverer\Query\Filter;

interface CriterionAdaptor
{

    public function prepareCriterion(Criterion $criterion): mixed;

}
