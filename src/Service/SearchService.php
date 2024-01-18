<?php

namespace SilverStripe\Search\Service;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Search\Analytics\AnalyticsData;
use SilverStripe\Search\Query\Query;
use SilverStripe\Search\Service\Results\Results;

class SearchService
{

    use Injectable;

    private ?SearchServiceAdaptor $adaptor = null;

    private static array $dependencies = [
        'adaptor' => '%$' . SearchServiceAdaptor::class,
    ];

    public function setAdaptor(?SearchServiceAdaptor $adaptor): void
    {
        $this->adaptor = $adaptor;
    }

    public function search(Query $query, ?string $indexName = null): Results
    {
        return $this->adaptor->search($query, $indexName);
    }

    public function processAnalytics(AnalyticsData $analyticsData): void
    {
        $this->adaptor->processAnalytics($analyticsData);
    }

}
