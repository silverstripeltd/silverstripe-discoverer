<?php

namespace SilverStripe\Discoverer\Service;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\Discoverer\Query\Query;
use SilverStripe\Discoverer\Query\Suggestion;
use SilverStripe\Discoverer\Service\Blocklist\QueryBlocklist;
use SilverStripe\Discoverer\Service\Interfaces\ProcessAnalyticsAdaptor;
use SilverStripe\Discoverer\Service\Interfaces\QuerySuggestionAdaptor;
use SilverStripe\Discoverer\Service\Interfaces\SearchAdaptor;
use SilverStripe\Discoverer\Service\Interfaces\SpellingSuggestionAdaptor;
use SilverStripe\Discoverer\Service\Results\Results;
use SilverStripe\Discoverer\Service\Results\Suggestions;

class SearchService
{

    use Injectable;

    private ?SearchAdaptor $searchAdaptor = null;

    private ?QuerySuggestionAdaptor $querySuggestionAdaptor = null;

    private ?SpellingSuggestionAdaptor $spellingSuggestionAdaptor = null;

    private ?ProcessAnalyticsAdaptor $processAnalyticsAdaptor = null;

    private ?QueryBlocklist $queryBlocklist = null;

    private static array $dependencies = [
        'searchAdaptor' => '%$' . SearchAdaptor::class,
        'querySuggestionAdaptor' => '%$' . QuerySuggestionAdaptor::class,
        'spellingSuggestionAdaptor' => '%$' . SpellingSuggestionAdaptor::class,
        'processAnalyticsAdaptor' => '%$' . ProcessAnalyticsAdaptor::class,
        'queryBlocklist' => '%$' . QueryBlocklist::class,
    ];

    public function __construct(private readonly ?string $indexPrefix = null)
    {
    }

    public function setSearchAdaptor(?SearchAdaptor $searchAdaptor): void
    {
        $this->searchAdaptor = $searchAdaptor;
    }

    public function setQuerySuggestionAdaptor(?QuerySuggestionAdaptor $querySuggestionAdaptor): void
    {
        $this->querySuggestionAdaptor = $querySuggestionAdaptor;
    }

    public function setSpellingSuggestionAdaptor(?SpellingSuggestionAdaptor $spellingSuggestionAdaptor): void
    {
        $this->spellingSuggestionAdaptor = $spellingSuggestionAdaptor;
    }

    public function setProcessAnalyticsAdaptor(?ProcessAnalyticsAdaptor $processAnalyticsAdaptor): void
    {
        $this->processAnalyticsAdaptor = $processAnalyticsAdaptor;
    }

    public function setQueryBlocklist(?QueryBlocklist $queryBlocklist): void
    {
        $this->queryBlocklist = $queryBlocklist;
    }

    /**
     * A query containing a blocked keyword returns successful, empty results without calling the search service, so
     * the visitor sees "no results" rather than an error.
     */
    public function search(Query $query, string $indexSuffix): Results
    {
        if ($this->isBlocked($query->getQueryString())) {
            return Results::create(200, $query);
        }

        return $this->searchAdaptor->process($query, $indexSuffix);
    }

    public function querySuggestion(Suggestion $suggestion, string $indexSuffix): Suggestions
    {
        if ($this->isBlocked($suggestion->getQueryString())) {
            return Suggestions::create(200);
        }

        return $this->querySuggestionAdaptor->process($suggestion, $indexSuffix);
    }

    public function spellingSuggestion(Suggestion $suggestion, string $indexSuffix): Suggestions
    {
        if ($this->isBlocked($suggestion->getQueryString())) {
            return Suggestions::create(200);
        }

        return $this->spellingSuggestionAdaptor->process($suggestion, $indexSuffix);
    }

    public function processAnalytics(AnalyticsData $analyticsData): void
    {
        $this->processAnalyticsAdaptor->process($analyticsData);
    }

    private function isBlocked(?string $queryString): bool
    {
        return $this->queryBlocklist?->isBlocked($queryString) ?? false;
    }

    public function environmentizeIndex(string $indexSuffix): string
    {
        if ($this->indexPrefix) {
            return sprintf('%s-%s', $this->indexPrefix, $indexSuffix);
        }

        return $indexSuffix;
    }

}
