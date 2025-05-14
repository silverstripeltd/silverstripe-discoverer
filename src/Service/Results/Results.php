<?php

namespace SilverStripe\Discoverer\Service\Results;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Discoverer\Query\Query;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\ModelData;

class Results extends ModelData
{

    use Injectable;

    private Records $records;

    private Facets $facets;

    private ?string $indexName = null;

    private bool $success = false;

    public function __construct(private readonly Query $query)
    {
        parent::__construct();

        $this->records = Records::create(ArrayList::create());
        $this->facets = Facets::create();
    }

    public function forTemplate(): string
    {
        return $this->renderWith(static::class);
    }

    public function getRecords(): ?Records
    {
        return $this->records;
    }

    public function addRecord(Record $record): static
    {
        $this->records->add($record);

        return $this;
    }

    public function getFacets(): ?Facets
    {
        return $this->facets;
    }

    public function addFacet(Facet $facet): static
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

    public function setIndexName(?string $indexName): static
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
