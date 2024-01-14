<?php

namespace SilverStripe\Search\Filter;

interface CriteriaAdaptor
{

    public function prepareCriteria(Criteria $criteria): mixed;

}
