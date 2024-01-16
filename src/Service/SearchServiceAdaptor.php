<?php

namespace SilverStripe\Search\Service;

use SilverStripe\Search\Query\Query;
use SilverStripe\Search\Service\Result\Results;

interface SearchServiceAdaptor
{

    public function search(Query $query, ?string $indexName = null): Results;

    public function logClickThrough(): void;

}
