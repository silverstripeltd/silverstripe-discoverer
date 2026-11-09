<?php

namespace SilverStripe\Discoverer\Query\Boost;

/**
 * Translates a `BoostCollection` into a search provider's request format.
 *
 * Each provider registers its own implementation through the Injector; providers without boost support fall back to
 * `NullBoostAdaptor`.
 */
interface BoostAdaptor
{

    /**
     * Translate the boosts in a collection into the provider's request format.
     *
     * @param BoostCollection $boostCollection the boosts to translate, keyed by field name
     * @return mixed - the boosts in the provider's format, or `null` when the provider ignores boosts
     */
    public function prepareBoosts(BoostCollection $boostCollection): mixed;

}
