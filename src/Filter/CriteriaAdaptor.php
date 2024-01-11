<?php

namespace SilverStripe\Search\Filter;

interface CriteriaAdaptor
{

    public function prepareClause(Criteria $criteria): mixed;

}
