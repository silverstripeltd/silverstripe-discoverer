<?php

namespace SilverStripe\Discoverer\Service;

use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\Discoverer\Query\Query;
use SilverStripe\Discoverer\Query\Suggestion;
use SilverStripe\Discoverer\Service;
use SilverStripe\Discoverer\Service\Results\Results;
use SilverStripe\Discoverer\Service\Results\Suggestions;

/**
 * Please be aware that we intend to replace this interface in v2. We want to move to a paradigm where each search
 * function has its own interface and default (empty) implementation provided by Discoverer. This way, we are able to
 * expand functionality in the future without causing breaking changes to this interface
 *
 * @see Service\Interfaces\SpellingSuggestionAdaptor
 * @see Service\Adaptors\SpellingSuggestionAdaptor
 * For an example of the new paradigm that we'll be using (one interface per search function)
 */
interface SearchServiceAdaptor
{

    public function search(Query $query, string $indexName): Results;

    public function querySuggestion(Suggestion $suggestion, string $indexName): Suggestions;

    public function processAnalytics(AnalyticsData $analyticsData): void;

}
