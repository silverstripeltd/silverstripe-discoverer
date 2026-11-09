<?php

namespace SilverStripe\Discoverer\Tests\Query\Boost;

use SilverStripe\Discoverer\Query\Boost\BoostAdaptor;
use SilverStripe\Discoverer\Query\Boost\BoostCollection;
use SilverStripe\Discoverer\Query\Boost\ProximityBoost;
use SilverStripe\Discoverer\Query\Boost\ValueBoost;

class MockBoostAdaptor implements BoostAdaptor
{

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
