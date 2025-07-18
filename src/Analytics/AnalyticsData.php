<?php

namespace SilverStripe\Discoverer\Analytics;

use JsonSerializable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Model\ModelData;
use stdClass;

class AnalyticsData extends ModelData implements JsonSerializable
{

    use Injectable;

    private ?string $indexName = null;

    private ?string $queryString = null;

    private mixed $documentId = null;

    private mixed $requestId = null;

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

    public function forTemplate(): string
    {
        $data = $this->getDataArray();

        if (!$data) {
            return '';
        }

        $query = [
            '_searchAnalytics' => base64_encode(json_encode($data)),
        ];

        return http_build_query($query);
    }

    public function jsonSerialize(): array|stdClass
    {
        $data = $this->getDataArray();

        if (!$data) {
            // Return an empty stdClass, so that json_encode provides an empty object (rather than an empty array)
            return new stdClass();
        }

        return $data;
    }

    private function getDataArray(): array
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
