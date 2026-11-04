<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Tests\Analytics;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Discoverer\Analytics\AnalyticsData;

/**
 * Rejects any click whose request carries the veto header
 */
class VetoAnalyticsExtension extends Extension implements TestOnly
{

    public const string HEADER = 'X-Test-Veto';

    protected function updateShouldProcess(
        bool &$result,
        HTTPRequest $request,
        HTTPResponse $response,
        AnalyticsData $data
    ): void {
        if ($request->getHeader(self::HEADER)) {
            $result = false;
        }
    }

}
