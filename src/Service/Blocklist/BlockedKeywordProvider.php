<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Service\Blocklist;

/**
 * Supplies the keywords and phrases that stop a search from being sent to the search service.
 *
 * Implementations are called on every search, autocomplete and spelling suggestion request, so any that read from a
 * database or remote source should cache the list.
 */
interface BlockedKeywordProvider
{

    /**
     * @return string[] Single words or short phrases. Matching is case-insensitive and on whole words only.
     */
    public function getBlockedKeywords(): array;

}
