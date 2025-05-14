<?php

namespace SilverStripe\Discoverer\Service\Adaptors;

use BadMethodCallException;
use SilverStripe\Discoverer\Query\Query;
use SilverStripe\Discoverer\Service\Interfaces\SearchAdaptor as SearchAdaptorInterface;
use SilverStripe\Discoverer\Service\Results\Results;

class SearchAdaptor implements SearchAdaptorInterface
{

    public function process(Query $query, string $indexSuffix): Results
    {
        throw new BadMethodCallException('Search adaptor has not been implemented');
    }

}
