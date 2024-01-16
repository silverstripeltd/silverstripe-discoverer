<?php

namespace SilverStripe\Search\Service\Result;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\Search\Query\Query;
use SilverStripe\Search\Service\Facet\Facet;
use SilverStripe\View\ViewableData;

class Results
{

    use Injectable;

    /**
     * @var PaginatedList|ViewableData[]|null
     */
    private ?PaginatedList $results = null;

    /**
     * @var ArrayList|Facet[]|null $facets
     */
    private ?ArrayList $facets = null;

    private ?string $indexName = null;

    public function __construct(private readonly Query $query)
    {
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    public function getIndexName(): ?string
    {
        return $this->indexName;
    }

    public function setIndexName(?string $indexName): self
    {
        $this->indexName = $indexName;

        return $this;
    }

}
