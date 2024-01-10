<?php

namespace SilverStripe\Search\Query;

abstract class Query
{

    public function __construct(private readonly string $queryString = '')
    {}

    public function getQueryString(): ?string
    {
        return $this->queryString;
    }

    abstract public function addSort(string $fieldName, string $direction): self;

    abstract public function addSorts(array $sortMethods): self;

    abstract public function setFilters(array $filter): self;

    abstract public function setFacets(array $facet): self;

    abstract public function addResultField(string $field, string $type, int $size): self;

    abstract public function addSearchField(string $field, int $weight): self;

    abstract public function setPageSize(int $pageSize): self;

    abstract public function setPageNum(int $pageNum): self;

    abstract public function setPagination(int $pageSize, int $pageNum): self;

    abstract public function hasPagination(): bool;

}
