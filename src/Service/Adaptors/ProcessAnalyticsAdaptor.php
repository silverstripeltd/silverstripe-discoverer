<?php

namespace SilverStripe\Discoverer\Service\Adaptors;

use BadMethodCallException;
use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\Discoverer\Service\Interfaces\ProcessAnalyticsAdaptor as ProcessAnalyticsAdaptorInterface;

class ProcessAnalyticsAdaptor implements ProcessAnalyticsAdaptorInterface
{

    public function process(AnalyticsData $analyticsData): void
    {
        throw new BadMethodCallException('Analytics adaptor has not been implemented');
    }

}
