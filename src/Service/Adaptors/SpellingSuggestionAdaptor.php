<?php

namespace SilverStripe\Discoverer\Service\Adaptors;

use BadMethodCallException;
use SilverStripe\Discoverer\Query\Suggestion;
use SilverStripe\Discoverer\Service\Interfaces\SpellingSuggestionAdaptor as SpellingSuggestionAdaptorInterface;
use SilverStripe\Discoverer\Service\Results\Suggestions;

class SpellingSuggestionAdaptor implements SpellingSuggestionAdaptorInterface
{

    public function process(Suggestion $suggestion, string $indexSuffix): Suggestions
    {
        throw new BadMethodCallException('Spelling suggestion adaptor has not been implemented');
    }

}
