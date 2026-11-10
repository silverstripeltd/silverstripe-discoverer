<?php

namespace SilverStripe\Discoverer\Tests\Query\Boost;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Discoverer\Query\Boost\ValueBoost;

/**
 * Stands in for `ValueBoost` to prove `Boost::value()` builds through the Injector.
 */
class ValueBoostReplacement extends ValueBoost implements TestOnly
{

}
