<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Analytics;

use LogicException;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;

/**
 * Signs and verifies search analytics payloads with an HMAC, so that click events can only be recorded for links that
 * this site rendered. Signing is only active when the SEARCH_ANALYTICS_SECRET environment variable is set.
 *
 * The signature depends only on the payload, so the same data always produces the same link. That keeps rendered
 * results pages identical between requests and safe to cache.
 */
class AnalyticsSigner
{

    use Injectable;

    public const string ENV_SECRET = 'SEARCH_ANALYTICS_SECRET';

    private const string ALGORITHM = 'sha256';

    public function isEnabled(): bool
    {
        return $this->getSecret() !== null;
    }

    /**
     * @param array<string, mixed> $payload The fields being signed
     * @throws LogicException When no secret is configured
     */
    public function sign(array $payload): string
    {
        $secret = $this->getSecret();

        if ($secret === null) {
            throw new LogicException(sprintf('Cannot sign analytics data: %s is not set', self::ENV_SECRET));
        }

        return hash_hmac(self::ALGORITHM, $this->canonicalise($payload), $secret);
    }

    /**
     * Constant-time comparison of the provided signature against the expected signature for this payload.
     *
     * @param array<string, mixed> $payload
     */
    public function verify(array $payload, string $signature): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        return hash_equals($this->sign($payload), $signature);
    }

    /**
     * Key order and JSON encoding flags are fixed so that the same payload always produces the same string, regardless
     * of the order in which fields were set or decoded.
     *
     * @param array<string, mixed> $payload
     */
    private function canonicalise(array $payload): string
    {
        ksort($payload);

        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function getSecret(): ?string
    {
        $secret = Environment::getEnv(self::ENV_SECRET);

        if (!is_string($secret) || $secret === '') {
            return null;
        }

        return $secret;
    }

}
