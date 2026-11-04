<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Tests\Analytics;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\Discoverer\Analytics\AnalyticsRequestValidator;
use SilverStripe\Discoverer\Analytics\CrawlerDetector;

class AnalyticsRequestValidatorTest extends SapphireTest
{

    protected $usesDatabase = false;

    public function testEveryCheckIsOffByDefault(): void
    {
        $request = new HTTPRequest('POST', 'page');
        $request->addHeader('User-Agent', 'curl/8.4.0');
        $request->addHeader('Sec-Fetch-Site', 'cross-site');
        $data = AnalyticsData::create()->setDocumentId('doc-1');

        $validator = AnalyticsRequestValidator::create();

        $this->assertNull($validator->getRequestRejectionReason($request));
        $this->assertTrue($validator->shouldProcess($request, HTTPResponse::create('', 500), $data));
    }

    public function testValidatorResolvesWithDefaultDependencies(): void
    {
        AnalyticsRequestValidator::config()->set('allowed_methods', ['GET']);
        AnalyticsRequestValidator::config()->set('ignore_crawlers', true);
        CrawlerDetector::config()->set('use_crawler_detect_library', false);

        $validator = AnalyticsRequestValidator::create();

        $post = new HTTPRequest('POST', 'page');
        $crawler = new HTTPRequest('GET', 'page');
        $crawler->addHeader('User-Agent', 'curl/8.4.0');

        $this->assertSame(AnalyticsRequestValidator::REASON_METHOD, $validator->getRequestRejectionReason($post));
        $this->assertSame(AnalyticsRequestValidator::REASON_CRAWLER, $validator->getRequestRejectionReason($crawler));
    }

}
