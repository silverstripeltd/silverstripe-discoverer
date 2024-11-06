<?php

namespace SilverStripe\Discoverer\Service\Interfaces;

use SilverStripe\Discoverer\Query\Suggestion;
use SilverStripe\Discoverer\Service\Results\Suggestions;

interface SpellingSuggestionAdaptor
{

    public function process(Suggestion $suggestion, string $indexName): Suggestions;

}
