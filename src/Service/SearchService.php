<?php

namespace SilverStripe\Discoverer\Service;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\Discoverer\Query\Query;
use SilverStripe\Discoverer\Query\Suggestion;
use SilverStripe\Discoverer\Service\Interfaces\SpellingSuggestionAdaptor;
use SilverStripe\Discoverer\Service\Results\Results;
use SilverStripe\Discoverer\Service\Results\Suggestions;

class SearchService
{

    use Injectable;

    private ?SearchServiceAdaptor $adaptor = null;

    private ?SpellingSuggestionAdaptor $spellingSuggestionAdaptor = null;

    private static array $dependencies = [
        'adaptor' => '%$' . SearchServiceAdaptor::class,
        'spellingSuggestionAdaptor' => '%$' . SpellingSuggestionAdaptor::class,
    ];

    public function setAdaptor(?SearchServiceAdaptor $adaptor): void
    {
        $this->adaptor = $adaptor;
    }

    public function setSpellingSuggestionAdaptor(?SpellingSuggestionAdaptor $spellingSuggestionAdaptor): void
    {
        $this->spellingSuggestionAdaptor = $spellingSuggestionAdaptor;
    }

    public function search(Query $query, string $indexName): Results
    {
        return $this->adaptor->search($query, $indexName);
    }

    public function querySuggestion(Suggestion $suggestion, string $indexName): Suggestions
    {
        return $this->adaptor->querySuggestion($suggestion, $indexName);
    }

    public function spellingSuggestion(Suggestion $suggestion, string $indexName): Suggestions
    {
        return $this->spellingSuggestionAdaptor->process($suggestion, $indexName);
    }

    public function processAnalytics(AnalyticsData $analyticsData): void
    {
        $this->adaptor->processAnalytics($analyticsData);
    }

}
