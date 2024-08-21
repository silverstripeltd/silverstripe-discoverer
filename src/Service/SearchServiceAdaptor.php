<?php

namespace SilverStripe\Discoverer\Service;

use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\Discoverer\Query\Query;
use SilverStripe\Discoverer\Query\Suggestion;
use SilverStripe\Discoverer\Service\Results\Results;
use SilverStripe\Discoverer\Service\Results\Suggestions;

interface SearchServiceAdaptor
{

    public function search(Query $query, string $indexName): Results;

    public function querySuggestion(Suggestion $suggestion, string $indexName): Suggestions;

    public function processAnalytics(AnalyticsData $analyticsData): void;

}
