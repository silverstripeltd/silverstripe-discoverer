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
