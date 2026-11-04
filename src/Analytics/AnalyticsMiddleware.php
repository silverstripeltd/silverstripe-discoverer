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

    public const string REASON_MISSING_SIGNATURE = 'missing_signature';

    public const string REASON_INVALID_SIGNATURE = 'invalid_signature';

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
            $analyticsData = AnalyticsData::decode($request->getVar(AnalyticsData::QUERY_PARAM));

            if (!$analyticsData) {
                return $response;
            }

            $rejectionReason = $this->getSignatureRejectionReason($analyticsData);

            if ($rejectionReason !== null) {
                $this->logger?->debug(sprintf('Search analytics click not recorded: %s', $rejectionReason));

                return $response;
            }

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

    /**
     * Checks the signature of decoded analytics data.
     *
     * @return string|null One of the REASON_* constants, or null when signing isn't enabled or the signature is valid
     */
    public function getSignatureRejectionReason(AnalyticsData $analyticsData): ?string
    {
        $signer = AnalyticsSigner::singleton();

        if (!$signer->isEnabled()) {
            return null;
        }

        $signature = $analyticsData->getSignature();

        if ($signature === null) {
            return self::REASON_MISSING_SIGNATURE;
        }

        if (!$signer->verify($analyticsData->getPayload(), $signature)) {
            return self::REASON_INVALID_SIGNATURE;
        }

        return null;
    }

}
