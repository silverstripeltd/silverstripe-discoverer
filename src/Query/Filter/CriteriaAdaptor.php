<?php

namespace SilverStripe\Discoverer\Query\Filter;

interface CriteriaAdaptor
{

    public function prepareCriteria(Criteria $criteria): mixed;

}
