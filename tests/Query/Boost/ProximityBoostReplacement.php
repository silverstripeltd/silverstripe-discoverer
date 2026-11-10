<?php

namespace SilverStripe\Discoverer\Tests\Query\Boost;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Discoverer\Query\Boost\ProximityBoost;

/**
 * Stands in for `ProximityBoost` to prove `Boost::proximity()` builds through the Injector.
 */
class ProximityBoostReplacement extends ProximityBoost implements TestOnly
{

}
