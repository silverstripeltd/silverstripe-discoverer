<?php

namespace SilverStripe\Discoverer\Analytics;

use Psr\Log\LoggerInterface;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Discoverer\Service\SearchService;
use Throwable;

class AnalyticsMiddleware implements HTTPMiddleware
{

    use Configurable;
    use Injectable;

    public const ENV_ANALYTICS_ENABLED = 'SEARCH_ANALYTICS_ENABLED';

    private ?LoggerInterface $logger = null;

    private static array $dependencies = [
        'logger' => '%$' . LoggerInterface::class . '.errorhandler',
    ];

    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function process(HTTPRequest $request, callable $delegate): mixed
    {
        /** @var HTTPResponse $response */
        $response = $delegate($request);

        // This Middleware shouldn't be applied if this is false, but we should double-check
        if (!Environment::getEnv(self::ENV_ANALYTICS_ENABLED)) {
            return $response;
        }

        try {
            $data = base64_decode($request->getVar('_searchAnalytics') ?? '', true);

            if (!$data) {
                return null;
            }

            $data = json_decode($data, true);

            if (!$data) {
                return null;
            }

            $analyticsData = AnalyticsData::create();
            $analyticsData->setQueryString($data['queryString'] ?? null);
            $analyticsData->setRequestId($data['requestId'] ?? null);
            $analyticsData->setDocumentId($data['documentId'] ?? null);
            $analyticsData->setEngineName($data['engineName'] ?? null);

            $service = SearchService::create();
            $service->processAnalytics($analyticsData);
        } catch (Throwable $e) {
            // Log the error without breaking the page
            $this->logger->error(sprintf('Elastic error: %s', $e->getMessage()), ['elastic' => $e]);
        } finally {
            return $response;
        }
    }

}
