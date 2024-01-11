<?php

namespace SilverStripe\Search\Service;

use SilverStripe\Search\Query\Result;
use SilverStripe\SearchElastic\Query\SearchQuery;

interface SearchService
{

    public function search(SearchQuery $query, string $indexName): Result;

    public function multiSearch(): Result;

}
