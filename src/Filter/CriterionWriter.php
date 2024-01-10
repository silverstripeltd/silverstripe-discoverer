<?php

namespace SilverStripe\Search\Filter;

interface CriterionWriter
{

    public function generateClauseString(Criterion $criterion): string;

}
