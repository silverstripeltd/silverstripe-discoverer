<?php

namespace SilverStripe\Search\Service;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Search\Query\Query;

abstract class SearchService
{

    use Injectable;

    abstract public function search(Query $query, ?string $indexName = null): void;

    abstract public function multiSearch(): void;

    abstract public function spellingSuggestions(): void;

    abstract public function searchSuggestions(): void;

    abstract public function logClickThrough(): void;

}
