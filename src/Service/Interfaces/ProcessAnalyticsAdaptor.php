<?php

namespace SilverStripe\Discoverer\Service\Interfaces;

use SilverStripe\Discoverer\Analytics\AnalyticsData;

interface ProcessAnalyticsAdaptor
{

    public function process(AnalyticsData $analyticsData): void;

}
