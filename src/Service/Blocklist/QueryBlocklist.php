<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Service\Blocklist;

use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injectable;
use Throwable;

/**
 * Decides whether a search query should be blocked, using a BlockedKeywordProvider for the keywords and a
 * KeywordMatcher for the matching. Both can be replaced through the Injector.
 *
 * Only the query string sent to the search service is checked. Suggestions returned by the service (autocomplete or
 * "did you mean") are not filtered.
 *
 * If the provider or matcher fails, the query is allowed, so a blocklist problem never stops search working.
 */
class QueryBlocklist
{

    use Injectable;

    private static array $dependencies = [
        'keywordProvider' => '%$' . BlockedKeywordProvider::class,
        'keywordMatcher' => '%$' . KeywordMatcher::class,
        'logger' => '%$' . LoggerInterface::class,
    ];

    private ?BlockedKeywordProvider $keywordProvider = null;

    private ?KeywordMatcher $keywordMatcher = null;

    private ?LoggerInterface $logger = null;

    public function setKeywordProvider(?BlockedKeywordProvider $keywordProvider): void
    {
        $this->keywordProvider = $keywordProvider;
    }

    public function setKeywordMatcher(?KeywordMatcher $keywordMatcher): void
    {
        $this->keywordMatcher = $keywordMatcher;
    }

    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function isBlocked(?string $queryString): bool
    {
        if ($queryString === null || trim($queryString) === '' || !$this->keywordProvider || !$this->keywordMatcher) {
            return false;
        }

        try {
            $keywords = $this->keywordProvider->getBlockedKeywords();

            if (!$keywords) {
                return false;
            }

            $match = $this->keywordMatcher->findMatch($queryString, $keywords);
        } catch (Throwable $e) {
            $this->logger?->warning(
                sprintf('Search blocklist could not be checked: %s', $e->getMessage()),
                ['exception' => $e]
            );

            return false;
        }

        if ($match === null) {
            return false;
        }

        // Only the keyword is logged, because the full query can contain personal information
        $this->logger?->debug('Search query blocked', ['keyword' => $match]);

        return true;
    }

}
