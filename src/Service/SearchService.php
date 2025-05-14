<?php

namespace SilverStripe\Discoverer\Service;

use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\Discoverer\Query\Query;
use SilverStripe\Discoverer\Query\Suggestion;
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

    private static array $dependencies = [
        'searchAdaptor' => '%$' . SearchAdaptor::class,
        'querySuggestionAdaptor' => '%$' . QuerySuggestionAdaptor::class,
        'spellingSuggestionAdaptor' => '%$' . SpellingSuggestionAdaptor::class,
        'processAnalyticsAdaptor' => '%$' . ProcessAnalyticsAdaptor::class,
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

    public function search(Query $query, string $indexSuffix): Results
    {
        return $this->searchAdaptor->process($query, $indexSuffix);
    }

    public function querySuggestion(Suggestion $suggestion, string $indexSuffix): Suggestions
    {
        return $this->querySuggestionAdaptor->process($suggestion, $indexSuffix);
    }

    public function spellingSuggestion(Suggestion $suggestion, string $indexSuffix): Suggestions
    {
        return $this->spellingSuggestionAdaptor->process($suggestion, $indexSuffix);
    }

    public function processAnalytics(AnalyticsData $analyticsData): void
    {
        $this->processAnalyticsAdaptor->process($analyticsData);
    }

    public function environmentizeIndex(string $indexSuffix): string
    {
        $variant = Environment::getEnv($this->indexPrefix);

        if ($variant) {
            return sprintf('%s-%s', $variant, $indexSuffix);
        }

        return $indexSuffix;
    }

}
