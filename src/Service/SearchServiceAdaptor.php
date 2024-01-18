<?php

namespace SilverStripe\Search\Service;

use SilverStripe\Search\Analytics\AnalyticsData;
use SilverStripe\Search\Query\Query;
use SilverStripe\Search\Service\Results\Results;

interface SearchServiceAdaptor
{

    public function search(Query $query, ?string $indexName = null): Results;

    public function processAnalytics(AnalyticsData $analyticsData): void;

}
