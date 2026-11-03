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

/**
 * Records search result clicks for requests carrying the _searchAnalytics param, once the page has been rendered and
 * the request has been accepted by AnalyticsRequestValidator.
 *
 * Analytics must never break the page: any failure is logged and the original response is returned.
 */
class AnalyticsMiddleware implements HTTPMiddleware
{

    use Configurable;
    use Injectable;

    public const string ENV_ANALYTICS_ENABLED = 'SEARCH_ANALYTICS_ENABLED';

    private ?LoggerInterface $logger = null;

    private ?AnalyticsRequestValidator $validator = null;

    private static array $dependencies = [
        'logger' => '%$' . LoggerInterface::class . '.errorhandler',
        'validator' => '%$' . AnalyticsRequestValidator::class,
    ];

    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setValidator(?AnalyticsRequestValidator $validator): void
    {
        $this->validator = $validator;
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
                return $response;
            }

            $data = json_decode($data, true);

            if (!$data || !is_array($data)) {
                return $response;
            }

            $analyticsData = AnalyticsData::create();
            $analyticsData->setQueryString($data['queryString'] ?? null);
            $analyticsData->setRequestId($data['requestId'] ?? null);
            $analyticsData->setDocumentId($data['documentId'] ?? null);
            $analyticsData->setIndexName($data['indexName'] ?? null);

            if ($this->validator && !$this->validator->shouldProcess($request, $response, $analyticsData)) {
                return $response;
            }

            $service = SearchService::create();
            $service->processAnalytics($analyticsData);
        } catch (Throwable $e) {
            $this->logError($e);
        }

        return $response;
    }

    /**
     * Logging is guarded as well, because a logger can throw (e.g. when its log file can't be written), and analytics
     * must never turn a rendered page into an error.
     */
    private function logError(Throwable $error): void
    {
        try {
            $this->logger?->error(sprintf('Message: %s', $error->getMessage()), ['exception' => $error]);
        } catch (Throwable) {
            return;
        }
    }

}
