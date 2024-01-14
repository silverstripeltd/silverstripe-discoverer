<?php

namespace SilverStripe\Search\Filter;

interface CriterionAdaptor
{

    public function prepareCriterion(Criterion $criterion): mixed;

}
