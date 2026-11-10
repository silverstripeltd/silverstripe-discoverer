<?php

namespace SilverStripe\Discoverer\Query\Boost;

/**
 * The default boost adaptor, for search providers that do not support query-time boosts.
 *
 * It ignores every boost, so a provider that registers no `BoostAdaptor` of its own keeps working.
 */
class NullBoostAdaptor implements BoostAdaptor
{

    /**
     * Accept every boost, as the provider ignores them all.
     *
     * @param string $field the name of the field being boosted
     * @param Boost $boost the boost to accept
     */
    public function validateBoost(string $field, Boost $boost): void
    {
        // Nothing to check, as every boost is ignored when the request is prepared
    }

    /**
     * Ignore the boosts in a collection, as the provider does not use them.
     *
     * @param BoostCollection $boostCollection the boosts to ignore
     * @return mixed - always `null`
     */
    public function prepareBoosts(BoostCollection $boostCollection): mixed
    {
        return null;
    }

}
