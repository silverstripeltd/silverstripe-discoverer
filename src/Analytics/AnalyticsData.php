<?php

namespace SilverStripe\Discoverer\Analytics;

use JsonSerializable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Model\ModelData;
use SilverStripe\ORM\FieldType\DBDatetime;

class AnalyticsData extends ModelData implements JsonSerializable
{

    use Injectable;

    public const string QUERY_PARAM = '_searchAnalytics';

    public const string KEY_TIMESTAMP = 'ts';

    public const string KEY_SIGNATURE = 'sig';

    private ?string $indexName = null;

    private ?string $queryString = null;

    private mixed $documentId = null;

    private mixed $requestId = null;

    private ?int $timestamp = null;

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
     * Unix timestamp of when a signed link was generated. Only present on data decoded from a signed link.
     */
    public function getTimestamp(): ?int
    {
        return $this->timestamp;
    }

    public function setTimestamp(?int $timestamp): AnalyticsData
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * HMAC of the payload and timestamp. Only present on data decoded from a signed link.
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
     * When SEARCH_ANALYTICS_SECRET is set, the encoded payload also carries a timestamp and signature so that the
     * click can be verified when the link is followed.
     */
    public function forTemplate(): string
    {
        $data = $this->getPayload();

        if (!$data) {
            return '';
        }

        $signer = AnalyticsSigner::singleton();

        if ($signer->isEnabled()) {
            $timestamp = DBDatetime::now()->getTimestamp();
            $data[self::KEY_TIMESTAMP] = $timestamp;
            $data[self::KEY_SIGNATURE] = $signer->sign($this->getPayload(), $timestamp);
        }

        $query = [
            self::QUERY_PARAM => base64_encode(json_encode($data)),
        ];

        return http_build_query($query);
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
