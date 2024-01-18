<?php

namespace SilverStripe\Search\Analytics;

use Psr\Log\LoggerInterface;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Search\Service\SearchService;
use Throwable;

class AnalyticsMiddleware implements HTTPMiddleware
{

    use Configurable;
    use Injectable;

    public ?LoggerInterface $logger = null;

    private static array $dependencies = [
        'logger' => '%$' . LoggerInterface::class . '.errorhandler',
    ];

    private static bool $enable_analytics = false;

    public function process(HTTPRequest $request, callable $delegate): mixed
    {
        // If you want normal behaviour to occur, make sure you call $delegate($request)
        $response = $delegate($request);

        try {
            $data = base64_decode($request->getVar('_searchAnalytics') ?? '');

            if (!$data) {
                return $response;
            }

            $data = json_decode($data);

            if (!$data) {
                return $response;
            }

            $analyticsData = AnalyticsData::create();
            $analyticsData->setQuery($data['queryString'] ?? null);
            $analyticsData->setRequestId($data['requestId'] ?? null);
            $analyticsData->setDocumentId($data['documentId'] ?? null);
            $analyticsData->setEngineName($data['engineName'] ?? null);

            $service = SearchService::create();
            $service->processAnalytics($analyticsData);
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage());
        } finally {
            return $response;
        }
    }

}
