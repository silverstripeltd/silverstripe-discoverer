<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Service\Blocklist;

use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injectable;
use Throwable;

/**
 * Decides whether a search query contains a blocked keyword.
 *
 * A keyword matches when it appears in the query as a whole word (or, for a phrase, as consecutive whole words).
 * Matching ignores case and punctuation: with "casino" blocked, "Online CASINO!" is blocked but "casinos" is not.
 * Patterns and partial-word matches are deliberately not supported, so a keyword can't accidentally block unrelated
 * searches.
 *
 * If the keyword provider fails, the query is allowed, so a blocklist problem never stops search working.
 */
class QueryBlocklist
{

    use Injectable;

    private static array $dependencies = [
        'keywordProvider' => '%$' . BlockedKeywordProvider::class,
        'logger' => '%$' . LoggerInterface::class,
    ];

    private ?BlockedKeywordProvider $keywordProvider = null;

    private ?LoggerInterface $logger = null;

    public function setKeywordProvider(?BlockedKeywordProvider $keywordProvider): void
    {
        $this->keywordProvider = $keywordProvider;
    }

    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function isBlocked(?string $queryString): bool
    {
        $query = $this->normalise((string) $queryString);

        if ($query === '') {
            return false;
        }

        $paddedQuery = sprintf(' %s ', $query);

        foreach ($this->getNormalisedKeywords() as $keyword) {
            if (str_contains($paddedQuery, sprintf(' %s ', $keyword))) {
                $this->logger?->debug('Search query blocked', ['keyword' => $keyword]);

                return true;
            }
        }

        return false;
    }

    /**
     * Lower-cases the text and replaces everything other than letters and numbers with single spaces, so that words
     * can be compared regardless of case, punctuation or spacing.
     */
    public function normalise(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text) ?? '';

        return trim($text);
    }

    /**
     * @return string[]
     */
    private function getNormalisedKeywords(): array
    {
        try {
            $keywords = $this->keywordProvider?->getBlockedKeywords() ?? [];
        } catch (Throwable $e) {
            $this->logger?->warning(
                sprintf('Blocked search keywords could not be loaded: %s', $e->getMessage()),
                ['exception' => $e]
            );

            return [];
        }

        $normalised = [];

        foreach ($keywords as $keyword) {
            $keyword = $this->normalise((string) $keyword);

            if ($keyword === '') {
                continue;
            }

            $normalised[$keyword] = $keyword;
        }

        return array_values($normalised);
    }

}
