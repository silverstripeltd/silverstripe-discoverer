<?php

namespace SilverStripe\Search\Query;

interface SearchQuery
{

    public function setQuery(string $query): self;

    public function getQuery(): string;

    public function addSort(string $fieldName, string $direction): self;

    public function addSorts(array $sortMethods): self;

    public function addFilter(SearchFilter $filter): self;

    public function addFilters(array $filters): self;

    public function addFacet(SearchFacet $facet): self;

    public function addFacets(array $facets): self;

    public function addResultField(string $field, string $type, int $size): self;

    public function addResultFields(array $fields): self;

    public function addSearchField(string $field, int $weight): self;

    public function addSearchFields(array $fields): self;

    public function setPageSize(int $pageSize): self;

    public function setPageNum(int $pageNum): self;

    public function setPagination(int $pageSize, int $pageNum): self;

    public function hasPagination(): bool;

}
