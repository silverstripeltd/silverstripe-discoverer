<?php

namespace SilverStripe\Search\Filter;

interface CriteriaAdaptor
{

    public function generateClauseString(Criteria $criterion): string;

}
