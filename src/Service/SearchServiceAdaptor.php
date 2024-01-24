<?php

namespace SilverStripe\Discoverer\Service;

use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\Discoverer\Query\Query;
use SilverStripe\Discoverer\Service\Results\Results;

interface SearchServiceAdaptor
{

    public function search(Query $query, string $indexName): Results;

    public function processAnalytics(AnalyticsData $analyticsData): void;

}
