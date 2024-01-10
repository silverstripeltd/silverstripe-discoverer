<?php

namespace SilverStripe\Search\Filter;

interface CriteriaWriter
{

    public function generateClauseString(Criteria $criterion): string;

}
