<?php

namespace SilverStripe\Search\Service\Results;

use Exception;
use SilverStripe\Search\Analytics\AnalyticsData;
use SilverStripe\View\ViewableData;

class Record extends ViewableData
{

    /**
     * This is the one field that we attempt to make predictable
     */
    private ?AnalyticsData $analyticsData = null;

    public function __set($property, $value)
    {
        if (!$value instanceof Field) {
            throw new Exception('Field value must be an instance of SilverStripe\\Search\\Service\\Result\\Field');
        }

        parent::__set($property, $value);
    }

    public function getAnalyticsData(): ?AnalyticsData
    {
        return $this->analyticsData;
    }

    public function setAnalyticsData(?AnalyticsData $analyticsData): Record
    {
        $this->analyticsData = $analyticsData;

        return $this;
    }

}
