<?php

namespace SilverStripe\Discoverer\Service\Results;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Discoverer\Query\Query;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\ViewableData;

class Results extends ViewableData
{

    use Injectable;

    /**
     * @var Records|Record[]
     */
    private Records $records;

    /**
     * @var Facets|Facet[]
     */
    private Facets $facets;

    private ?string $indexName = null;

    private bool $success = false;

    public function __construct(private readonly Query $query)
    {
        $this->records = Records::create(ArrayList::create());
        $this->facets = Facets::create();
    }

    public function forTemplate(): DBHTMLText
    {
        return $this->renderWith(static::class);
    }

    public function getRecords(): ?Records
    {
        return $this->records;
    }

    public function addRecord(Record $record): self
    {
        $this->records->add($record);

        return $this;
    }

    public function getFacets(): ?Facets
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
