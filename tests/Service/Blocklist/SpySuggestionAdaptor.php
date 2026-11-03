<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Tests\Service\Blocklist;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Discoverer\Query\Suggestion;
use SilverStripe\Discoverer\Service\Interfaces\QuerySuggestionAdaptor;
use SilverStripe\Discoverer\Service\Interfaces\SpellingSuggestionAdaptor;
use SilverStripe\Discoverer\Service\Results\Suggestions;

/**
 * Counts suggestion requests it is asked to process, instead of sending them to a search service
 */
class SpySuggestionAdaptor implements QuerySuggestionAdaptor, SpellingSuggestionAdaptor, TestOnly
{

    public int $calls = 0;

    public function process(Suggestion $suggestion, string $indexSuffix): Suggestions
    {
        $this->calls++;

        return Suggestions::create(200);
    }

}
