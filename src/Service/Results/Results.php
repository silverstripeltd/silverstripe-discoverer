<?php

namespace SilverStripe\Discoverer\Service\Results;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Discoverer\Query\Query;
use SilverStripe\Model\List\ArrayList;

class Results extends Response
{

    use Injectable;

    private Records $records;

    private Facets $facets;

    private ?string $indexName = null;

    public function __construct(private readonly Query $query, int $statusCode)
    {
        parent::__construct($statusCode);

        $this->records = Records::create(ArrayList::create());
        $this->facets = Facets::create();
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

    public function jsonSerialize(): array
    {
        return [
            'records' => $this->getRecords()->jsonSerialize(),
            'facets' => $this->getFacets()->jsonSerialize(),
        ];
    }

}
