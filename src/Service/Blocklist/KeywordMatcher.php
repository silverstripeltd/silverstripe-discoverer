<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Service\Blocklist;

/**
 * Decides whether a search query contains any of the blocked keywords.
 *
 * Replace the default implementation through the Injector to change how matching works, for example for languages
 * written without spaces between words.
 */
interface KeywordMatcher
{

    /**
     * @param string[] $keywords Keywords as supplied by the BlockedKeywordProvider
     * @return string|null The keyword that matched, or null when the query isn't blocked
     */
    public function findMatch(string $queryString, array $keywords): ?string;

}
