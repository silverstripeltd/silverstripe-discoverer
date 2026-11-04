<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Tests\Analytics;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Discoverer\Analytics\AnalyticsData;

/**
 * Skips recording every click while $veto is true
 */
class VetoClickExtension extends Extension implements TestOnly
{

    public static bool $veto = false;

    protected function updateShouldRecordClick(
        bool &$shouldRecord,
        HTTPRequest $request,
        AnalyticsData $analyticsData
    ): void {
        if (self::$veto) {
            $shouldRecord = false;
        }
    }

}
