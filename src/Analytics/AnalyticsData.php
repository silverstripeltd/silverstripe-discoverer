<?php

namespace SilverStripe\Discoverer\Analytics;

use JsonSerializable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Model\ModelData;

class AnalyticsData extends ModelData implements JsonSerializable
{

    use Injectable;

    public const string QUERY_PARAM = '_searchAnalytics';

    public const string KEY_SIGNATURE = 'sig';

    /**
     * Key under which a click route's destination URL is included in the signed payload
     */
    public const string KEY_DESTINATION = 'url';

    private ?string $indexName = null;

    private ?string $queryString = null;

    private mixed $documentId = null;

    private mixed $requestId = null;

    private ?string $signature = null;

    public function getIndexName(): ?string
    {
        return $this->indexName;
    }

    public function setIndexName(?string $indexName): AnalyticsData
    {
        $this->indexName = $indexName;

        return $this;
    }

    public function getQueryString(): ?string
    {
        return $this->queryString;
    }

    public function setQueryString(?string $queryString): AnalyticsData
    {
        $this->queryString = $queryString;

        return $this;
    }

    public function getDocumentId(): mixed
    {
        return $this->documentId;
    }

    public function setDocumentId(mixed $documentId): AnalyticsData
    {
        $this->documentId = $documentId;

        return $this;
    }

    public function getRequestId(): mixed
    {
        return $this->requestId;
    }

    public function setRequestId(mixed $requestId): AnalyticsData
    {
        $this->requestId = $requestId;

        return $this;
    }

    /**
     * HMAC of the payload. Only present on data decoded from a signed link.
     */
    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function setSignature(?string $signature): AnalyticsData
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * When SEARCH_ANALYTICS_SECRET is set, the encoded payload also carries a signature so that the click can be
     * verified when the link is followed.
     */
    public function forTemplate(): string
    {
        $data = $this->getPayload();

        if (!$data) {
            return '';
        }

        $signer = AnalyticsSigner::singleton();

        if ($signer->isEnabled()) {
            $data[self::KEY_SIGNATURE] = $signer->sign($this->getPayload());
        }

        $query = [
            self::QUERY_PARAM => base64_encode(json_encode($data)),
        ];

        return http_build_query($query);
    }

    /**
     * Query string for a click route link: the destination URL plus the analytics data, signed together so that
     * neither can be changed. Requires SEARCH_ANALYTICS_SECRET.
     */
    public function forClickUrl(string $destination): string
    {
        $data = $this->getPayload();
        $data[self::KEY_SIGNATURE] = AnalyticsSigner::singleton()->sign($this->getDestinationPayload($destination));

        return http_build_query([
            self::KEY_DESTINATION => $destination,
            self::QUERY_PARAM => base64_encode(json_encode($data)),
        ]);
    }

    /**
     * The payload signed for a click route link: the analytics data plus the destination URL
     *
     * @return array<string, mixed>
     */
    public function getDestinationPayload(string $destination): array
    {
        $payload = $this->getPayload();
        $payload[self::KEY_DESTINATION] = $destination;

        return $payload;
    }

    /**
     * Creates analytics data from an encoded _searchAnalytics value.
     *
     * @return static|null Null when the value is missing, isn't base64 encoded JSON, or isn't a JSON object
     */
    public static function decode(mixed $encoded): ?static
    {
        if (!is_string($encoded) || $encoded === '') {
            return null;
        }

        $json = base64_decode($encoded, true);
        $data = $json ? json_decode($json, true) : null;

        if (!is_array($data) || !$data) {
            return null;
        }

        $signature = $data[self::KEY_SIGNATURE] ?? null;

        $analyticsData = static::create();
        $analyticsData->setQueryString($data['queryString'] ?? null);
        $analyticsData->setRequestId($data['requestId'] ?? null);
        $analyticsData->setDocumentId($data['documentId'] ?? null);
        $analyticsData->setIndexName($data['indexName'] ?? null);
        $analyticsData->setSignature(is_string($signature) ? $signature : null);

        return $analyticsData;
    }

    public function jsonSerialize(): array
    {
        return [
            'indexName' => $this->getIndexName(),
            'queryString' => $this->getQueryString(),
            'documentId' => $this->getDocumentId(),
            'requestId' => $this->getRequestId(),
        ];
    }

    /**
     * The populated analytics fields, as encoded into links and covered by the signature.
     *
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        $data = [];

        $indexName = $this->getIndexName();
        $query = $this->getQueryString();
        $documentId = $this->getDocumentId();
        $requestId = $this->getRequestId();

        if ($indexName) {
            $data['indexName'] = $indexName;
        }

        if ($query) {
            $data['queryString'] = $query;
        }

        if ($documentId) {
            $data['documentId'] = $documentId;
        }

        if ($requestId) {
            $data['requestId'] = $requestId;
        }

        return $data;
    }

}
