<?php

namespace SilverStripe\Discoverer\Analytics;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\View\ViewableData;

class AnalyticsData extends ViewableData
{

    use Injectable;

    private ?string $engineName = null;

    private ?string $queryString = null;

    private mixed $documentId = null;

    private mixed $requestId = null;

    public function getEngineName(): ?string
    {
        return $this->engineName;
    }

    public function setEngineName(?string $engineName): AnalyticsData
    {
        $this->engineName = $engineName;

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

    public function forTemplate(): ?string
    {
        $data = [];

        $engineName = $this->getEngineName();
        $query = $this->getQueryString();
        $documentId = $this->getDocumentId();
        $requestId = $this->getRequestId();

        if ($engineName) {
            $data['engineName'] = $engineName;
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

        if (!$data) {
            return null;
        }

        $query = [
            '_searchAnalytics' => base64_encode(json_encode($data)),
        ];

        return http_build_query($query);
    }

}
