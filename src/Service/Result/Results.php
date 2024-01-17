<?php

namespace SilverStripe\Search\Service\Result;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\Search\Query\Query;

class Results
{

    use Injectable;

    /**
     * Results (ideally) have a DataObject representing them, but that's not always the case
     *
     * @var PaginatedList|Record[]|null
     */
    private ?PaginatedList $records = null;

    /**
     * @var ArrayList|Facet[]|null $facets
     */
    private ?ArrayList $facets = null;

    private ?string $indexName = null;

    public function __construct(private readonly Query $query)
    {
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

}
