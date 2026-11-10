<?php

namespace SilverStripe\Discoverer\Tests\Query\Boost;

use SilverStripe\Discoverer\Query\Boost\Boost;
use SilverStripe\Discoverer\Query\Boost\BoostAdaptor;
use SilverStripe\Discoverer\Query\Boost\BoostCollection;
use SilverStripe\Discoverer\Query\Boost\ProximityBoost;
use SilverStripe\Discoverer\Query\Boost\ValueBoost;

/**
 * A boost adaptor for tests that accepts every boost and prepares each one as a plain array.
 */
class MockBoostAdaptor implements BoostAdaptor
{

    /**
     * @inheritDoc
     */
    public function validateBoost(string $field, Boost $boost): void
    {
    }

    /**
     * @inheritDoc
     */
    public function prepareBoosts(BoostCollection $boostCollection): mixed
    {
        $preparedBoosts = [];

        foreach ($boostCollection->getBoosts() as $field => $boosts) {
            foreach ($boosts as $boost) {
                $preparedBoost = ['factor' => $boost->getFactor()];

                if ($boost instanceof ValueBoost) {
                    $preparedBoost['type'] = 'value';
                    $preparedBoost['values'] = $boost->getValues();
                }

                if ($boost instanceof ProximityBoost) {
                    $preparedBoost['type'] = 'proximity';
                    $preparedBoost['center'] = $boost->getCenter();
                    $preparedBoost['function'] = $boost->getFunction();
                }

                $preparedBoosts[$field][] = $preparedBoost;
            }
        }

        return $preparedBoosts;
    }

}
