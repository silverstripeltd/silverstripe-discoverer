<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Service\Blocklist;

use Normalizer;
use SilverStripe\Core\Injector\Injectable;

/**
 * Default matcher: a keyword matches when it appears in the query as a whole word (or, for a phrase, as consecutive
 * whole words). Matching ignores case and punctuation: with "casino" blocked, "Online CASINO!" is blocked but
 * "casinos" is not. Patterns and partial-word matches are deliberately not supported, so a keyword can't accidentally
 * block unrelated searches.
 *
 * Words are found by splitting on anything that isn't a letter, combining mark or number, so this matcher suits
 * languages that separate words with spaces. It won't find words in languages written without them (e.g. Chinese,
 * Japanese or Thai).
 */
class WholeWordKeywordMatcher implements KeywordMatcher
{

    use Injectable;

    public function findMatch(string $queryString, array $keywords): ?string
    {
        $query = $this->normalise($queryString);

        if ($query === '') {
            return null;
        }

        $paddedQuery = sprintf(' %s ', $query);

        foreach ($keywords as $keyword) {
            $normalisedKeyword = $this->normalise((string) $keyword);

            if ($normalisedKeyword === '') {
                continue;
            }

            if (str_contains($paddedQuery, sprintf(' %s ', $normalisedKeyword))) {
                return $normalisedKeyword;
            }
        }

        return null;
    }

    /**
     * Converts the text to Unicode NFC, so an accent typed as a separate combining character matches the same
     * accented letter typed as one character. Then lower-cases it and replaces everything other than letters,
     * combining marks and numbers with single spaces, so that words can be compared regardless of case, punctuation
     * or spacing.
     */
    public function normalise(string $text): string
    {
        $text = Normalizer::normalize($text, Normalizer::FORM_C) ?: $text;
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^\p{L}\p{M}\p{N}]+/u', ' ', $text) ?? '';

        return trim($text);
    }

}
