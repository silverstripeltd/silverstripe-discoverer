<?php

namespace SilverStripe\Search\Analytics;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\View\ViewableData;

class AnalyticsData extends ViewableData
{

    use Injectable;

    private ?string $engineName = null;

    private ?string $query = null;

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

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function setQuery(?string $query): AnalyticsData
    {
        $this->query = $query;

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
        $params = [];

        $engineName = $this->getEngineName();
        $query = $this->getQuery();
        $documentId = $this->getDocumentId();
        $requestId = $this->getRequestId();

        if ($engineName) {
            $params['engineName'] = $engineName;
        }

        if ($query) {
            $params['query'] = $query;
        }

        if ($documentId) {
            $params['documentId'] = $documentId;
        }

        if ($requestId) {
            $params['requestId'] = $requestId;
        }

        if (!$params) {
            return null;
        }

        return http_build_query($params);
    }

}
