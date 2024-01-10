<?php

namespace SilverStripe\Search\Filter;

interface CriterionAdaptor
{

    public function prepareClause(Criterion $criterion): mixed;

}
