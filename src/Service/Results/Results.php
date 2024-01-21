<?php

namespace SilverStripe\Search\Service\Results;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\Search\Query\Query;
use SilverStripe\View\ViewableData;

class Results extends ViewableData
{

    use Injectable;

    /**
     * @var PaginatedList|Record[]
     */
    private PaginatedList $records;

    /**
     * @var ArrayList|Facet[]
     */
    private ArrayList $facets;

    private ?string $indexName = null;

    private bool $success = false;

    public function __construct(private readonly Query $query)
    {
        $this->records = PaginatedList::create(ArrayList::create());
        $this->facets = ArrayList::create();
    }

    public function getRecords(): ?PaginatedList
    {
        return $this->records;
    }

    public function addRecord(Record $record): self
    {
        $this->records->add($record);

        return $this;
    }

    public function getFacets(): ?ArrayList
    {
        return $this->facets;
    }

    public function addFacet(Facet $facet): self
    {
        $this->facets->add($facet);

        return $this;
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

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): Results
    {
        $this->success = $success;

        return $this;
    }

}
