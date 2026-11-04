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

    public const string ENV_ANALYTICS_ENABLED = 'SEARCH_ANALYTICS_ENABLED';

    public const string REASON_MISSING_SIGNATURE = 'missing_signature';

    public const string REASON_INVALID_SIGNATURE = 'invalid_signature';

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
            $analyticsData = AnalyticsData::decode($request->getVar(AnalyticsData::QUERY_PARAM));

            if (!$analyticsData) {
                return $response;
            }

            $rejectionReason = $this->getSignatureRejectionReason($analyticsData);

            if ($rejectionReason !== null) {
                $this->logger?->debug(sprintf('Search analytics click not recorded: %s', $rejectionReason));

                return $response;
            }

            $service = SearchService::create();
            $service->processAnalytics($analyticsData);
        } catch (Throwable $e) {
            // Log the error without breaking the page
            $this->logger->error(sprintf('Message: %s', $e->getMessage()), ['exception' => $e]);
        } finally {
            return $response;
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
