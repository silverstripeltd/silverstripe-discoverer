<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Tests\Service\Blocklist;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Query\Facet\FacetAdaptor;
use SilverStripe\Discoverer\Query\Filter\CriteriaAdaptor;
use SilverStripe\Discoverer\Query\Filter\CriterionAdaptor;
use SilverStripe\Discoverer\Query\Query;
use SilverStripe\Discoverer\Query\Suggestion;
use SilverStripe\Discoverer\Service\Blocklist\ConfigBlockedKeywordProvider;
use SilverStripe\Discoverer\Service\Interfaces\QuerySuggestionAdaptor;
use SilverStripe\Discoverer\Service\Interfaces\SearchAdaptor;
use SilverStripe\Discoverer\Service\Interfaces\SpellingSuggestionAdaptor;
use SilverStripe\Discoverer\Service\SearchService;
use SilverStripe\Discoverer\Tests\Query\Facet\MockFacetAdaptor;
use SilverStripe\Discoverer\Tests\Query\Filter\MockCriteriaAdaptor;
use SilverStripe\Discoverer\Tests\Query\Filter\MockCriterionAdaptor;

class SearchServiceBlocklistTest extends SapphireTest
{

    protected $usesDatabase = false;

    private SpySearchAdaptor $searchAdaptor;

    private SpySuggestionAdaptor $querySuggestionAdaptor;

    private SpySuggestionAdaptor $spellingSuggestionAdaptor;

    protected function setUp(): void
    {
        parent::setUp();

        ConfigBlockedKeywordProvider::config()->set('blocked_keywords', ['casino']);

        $this->searchAdaptor = new SpySearchAdaptor();
        $this->querySuggestionAdaptor = new SpySuggestionAdaptor();
        $this->spellingSuggestionAdaptor = new SpySuggestionAdaptor();

        Injector::inst()->registerService(new MockCriteriaAdaptor(), CriteriaAdaptor::class);
        Injector::inst()->registerService(new MockCriterionAdaptor(), CriterionAdaptor::class);
        Injector::inst()->registerService(new MockFacetAdaptor(), FacetAdaptor::class);
        Injector::inst()->registerService($this->searchAdaptor, SearchAdaptor::class);
        Injector::inst()->registerService($this->querySuggestionAdaptor, QuerySuggestionAdaptor::class);
        Injector::inst()->registerService($this->spellingSuggestionAdaptor, SpellingSuggestionAdaptor::class);
    }

    public function testBlockedSearchReturnsEmptySuccessfulResultsWithoutCallingService(): void
    {
        $query = Query::create('online casino');

        $results = SearchService::create()->search($query, 'main');

        $this->assertSame(0, $this->searchAdaptor->calls);
        $this->assertTrue($results->isSuccess());
        $this->assertSame(0, $results->getRecords()->count());
        $this->assertSame($query, $results->getQuery());
    }

    public function testAllowedSearchCallsService(): void
    {
        SearchService::create()->search(Query::create('library hours'), 'main');

        $this->assertSame(1, $this->searchAdaptor->calls);
    }

    public function testBlockedQuerySuggestionReturnsEmptySuggestionsWithoutCallingService(): void
    {
        $suggestions = SearchService::create()->querySuggestion(Suggestion::create('casino'), 'main');

        $this->assertSame(0, $this->querySuggestionAdaptor->calls);
        $this->assertTrue($suggestions->isSuccess());
        $this->assertSame([], $suggestions->getSuggestions());
    }

    public function testAllowedQuerySuggestionCallsService(): void
    {
        SearchService::create()->querySuggestion(Suggestion::create('libr'), 'main');

        $this->assertSame(1, $this->querySuggestionAdaptor->calls);
    }

    public function testBlockedSpellingSuggestionReturnsEmptySuggestionsWithoutCallingService(): void
    {
        $suggestions = SearchService::create()->spellingSuggestion(Suggestion::create('casino games'), 'main');

        $this->assertSame(0, $this->spellingSuggestionAdaptor->calls);
        $this->assertTrue($suggestions->isSuccess());
        $this->assertSame([], $suggestions->getSuggestions());
    }

    public function testAllowedSpellingSuggestionCallsService(): void
    {
        SearchService::create()->spellingSuggestion(Suggestion::create('libary'), 'main');

        $this->assertSame(1, $this->spellingSuggestionAdaptor->calls);
    }

}
