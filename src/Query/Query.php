<?php

namespace SilverStripe\Search\Query;

use SilverStripe\Search\Filter\Clause;
use SilverStripe\Search\Filter\Criteria;
use SilverStripe\Search\Filter\Criterion;

abstract class Query
{

    private Criteria $criteria;

    public function __construct(private readonly string $queryString = '')
    {
        $this->criteria = Criteria::createAll();
    }

    public function getQueryString(): ?string
    {
        return $this->queryString;
    }

    public function addFilter(Clause|string $targetOrClause, mixed $value = null, ?string $comparison = null): self
    {
        if ($targetOrClause instanceof Clause) {
            $this->criteria->addClause($targetOrClause);

            return $this;
        }

        $clause = Criterion::create($targetOrClause, $value, $comparison);
        $this->criteria->addClause($clause);

        return $this;
    }

    public function setFilterConjunction(string $conjunction): self
    {
        $this->criteria->setConjunction($conjunction);

        return $this;
    }

    abstract public function addSort(string $fieldName, string $direction): self;

    abstract public function addSorts(array $sortMethods): self;

    abstract public function setFacets(array $facet): self;

    abstract public function addResultField(string $field, string $type, int $size): self;

    abstract public function addSearchField(string $field, int $weight): self;

    abstract public function setPageSize(int $pageSize): self;

    abstract public function setPageNum(int $pageNum): self;

    abstract public function setPagination(int $pageSize, int $pageNum): self;

    abstract public function hasPagination(): bool;

}
