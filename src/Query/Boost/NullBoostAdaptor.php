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
