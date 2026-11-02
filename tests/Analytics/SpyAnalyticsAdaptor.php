<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Tests\Analytics;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\Discoverer\Service\Interfaces\ProcessAnalyticsAdaptor;
use Throwable;

/**
 * Records every click it is asked to process, instead of sending it to a search service
 */
class SpyAnalyticsAdaptor implements ProcessAnalyticsAdaptor, TestOnly
{

    /**
     * @var AnalyticsData[]
     */
    public array $processed = [];

    public ?Throwable $exception = null;

    public function process(AnalyticsData $analyticsData): void
    {
        if ($this->exception) {
            throw $this->exception;
        }

        $this->processed[] = $analyticsData;
    }

}
