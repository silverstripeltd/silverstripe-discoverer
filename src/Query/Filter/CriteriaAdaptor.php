<?php

namespace SilverStripe\Search\Query\Filter;

interface CriteriaAdaptor
{

    public function prepareCriteria(Criteria $criteria): mixed;

}
