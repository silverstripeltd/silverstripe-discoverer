<?php

namespace SilverStripe\Discoverer\Service\Adaptors;

use BadMethodCallException;
use SilverStripe\Discoverer\Query\Suggestion;
use SilverStripe\Discoverer\Service\Interfaces\QuerySuggestionAdaptor as QuerySuggestionAdaptorInterface;
use SilverStripe\Discoverer\Service\Results\Suggestions;

class QuerySuggestionAdaptor implements QuerySuggestionAdaptorInterface
{

    public function process(Suggestion $suggestion, string $indexSuffix): Suggestions
    {
        throw new BadMethodCallException('Query suggestion adaptor has not been implemented');
    }

}
