<?php

namespace SilverStripe\Discoverer\Query\Boost;

use InvalidArgumentException;

/**
 * Translates a `BoostCollection` into a search provider's request format.
 *
 * Each provider registers its own implementation through the Injector; providers without boost support fall back to
 * `NullBoostAdaptor`.
 */
interface BoostAdaptor
{

    /**
     * Check that the provider can accept a boost, so an unusable boost is rejected when it is added to a query.
     *
     * @param string $field the name of the field being boosted
     * @param Boost $boost the boost to check
     * @throws InvalidArgumentException When the provider cannot accept the boost
     */
    public function validateBoost(string $field, Boost $boost): void;

    /**
     * Translate the boosts in a collection into the provider's request format.
     *
     * @param BoostCollection $boostCollection the boosts to translate, keyed by field name
     * @return mixed - the boosts in the provider's format, or `null` when the provider ignores boosts
     */
    public function prepareBoosts(BoostCollection $boostCollection): mixed;

}
