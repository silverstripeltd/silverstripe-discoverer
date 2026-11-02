<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Tests\Analytics;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\Discoverer\Analytics\AnalyticsRequestValidator;

class AnalyticsRequestValidatorTest extends SapphireTest
{

    protected $usesDatabase = false;

    public function testValidatorResolvesWithDefaultDependencies(): void
    {
        $request = new HTTPRequest('POST', 'page');
        $data = AnalyticsData::create()->setDocumentId('doc-1');

        $validator = AnalyticsRequestValidator::create();

        $this->assertSame(AnalyticsRequestValidator::REASON_METHOD, $validator->getRequestRejectionReason($request));
        $this->assertFalse($validator->shouldProcess($request, HTTPResponse::create(), $data));
    }

}
