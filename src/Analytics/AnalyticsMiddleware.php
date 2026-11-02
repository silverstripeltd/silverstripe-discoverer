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
use SilverStripe\ORM\FieldType\DBDatetime;
use Throwable;

class AnalyticsMiddleware implements HTTPMiddleware
{

    use Configurable;
    use Injectable;

    public const string ENV_ANALYTICS_ENABLED = 'SEARCH_ANALYTICS_ENABLED';

    /**
     * Allowance for clock differences between servers when a signed link's timestamp is in the future
     */
    private const int MAX_CLOCK_SKEW = 60;

    /**
     * Seconds that a signed link remains valid after it was generated. Only applies when SEARCH_ANALYTICS_SECRET is
     * set. 0 disables the age check.
     *
     * @config
     */
    private static int $max_link_age = 3600;

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
            $data = base64_decode($request->getVar(AnalyticsData::QUERY_PARAM) ?? '', true);

            if (!$data) {
                return $response;
            }

            $data = json_decode($data, true);

            if (!$data) {
                return $response;
            }

            $timestamp = $data[AnalyticsData::KEY_TIMESTAMP] ?? null;
            $signature = $data[AnalyticsData::KEY_SIGNATURE] ?? null;

            $analyticsData = AnalyticsData::create();
            $analyticsData->setQueryString($data['queryString'] ?? null);
            $analyticsData->setRequestId($data['requestId'] ?? null);
            $analyticsData->setDocumentId($data['documentId'] ?? null);
            $analyticsData->setIndexName($data['indexName'] ?? null);
            $analyticsData->setTimestamp(is_int($timestamp) ? $timestamp : null);
            $analyticsData->setSignature(is_string($signature) ? $signature : null);

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
     * Checks the signature and timestamp of decoded analytics data. Returns null when the data is acceptable (or when
     * signing is not enabled), otherwise a short description of why it was rejected.
     */
    public function getSignatureRejectionReason(AnalyticsData $analyticsData): ?string
    {
        $signer = AnalyticsSigner::singleton();

        if (!$signer->isEnabled()) {
            return null;
        }

        $timestamp = $analyticsData->getTimestamp();
        $signature = $analyticsData->getSignature();

        if ($timestamp === null || $signature === null) {
            return 'missing signature';
        }

        if (!$signer->verify($analyticsData->getPayload(), $timestamp, $signature)) {
            return 'invalid signature';
        }

        $age = DBDatetime::now()->getTimestamp() - $timestamp;

        if ($age < -self::MAX_CLOCK_SKEW) {
            return 'signed link timestamp is in the future';
        }

        $maxLinkAge = (int) $this->config()->get('max_link_age');

        if ($maxLinkAge > 0 && $age > $maxLinkAge) {
            return 'signed link has expired';
        }

        return null;
    }

}
