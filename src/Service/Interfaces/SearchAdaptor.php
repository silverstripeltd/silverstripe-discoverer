<?php

namespace SilverStripe\Discoverer\Service\Interfaces;

use SilverStripe\Discoverer\Query\Query;
use SilverStripe\Discoverer\Service\Results\Results;

interface SearchAdaptor
{

    public function process(Query $query, string $indexSuffix): Results;

}
