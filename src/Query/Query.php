<?php

namespace SilverStripe\Search\Query;

use Exception;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Search\Query\Facet\Facet;
use SilverStripe\Search\Query\Facet\FacetCollection;
use SilverStripe\Search\Query\Filter\Clause;
use SilverStripe\Search\Query\Filter\Criteria;
use SilverStripe\Search\Query\Filter\Criterion;

class Query
{

    use Injectable;

    public const SORT_ASC = 'ASC';
    public const SORT_DESC = 'DESC';

    public const SORTS = [
        self::SORT_ASC,
        self::SORT_DESC,
    ];

    /**
     * The top level collection of clauses (Criteria) always uses an AND conjunction. From here, you can still call
     * a mixture of filterAny() and filter(), but between each of these will be an AND condition
     *
     * EG: Let's say you do this
     *
     * $query->filterAny[...conditions]
     * $query->filterAny[...otherConditions]
     *
     * You will end up with (...conditions) AND (...otherConditions)
     */
    private Criteria $criteria;

    private FacetCollection $facetCollection;

    private ?int $pageNum = null;

    private ?int $pageSize = null;

    private array $resultFields = [];

    private array $searchFields = [];

    private array $sort = [];

    public function __construct(private string $queryString = '')
    {
        // See the docblock on self::$filter for some details
        $this->criteria = Criteria::createAll();
        $this->facetCollection = FacetCollection::create();
    }

    public function setQueryString(string $queryString): void
    {
        $this->queryString = $queryString;
    }

    public function getQueryString(): ?string
    {
        return $this->queryString;
    }

    public function getFacetCollection(): FacetCollection
    {
        return $this->facetCollection;
    }

    public function addFacet(Facet $facet): self
    {
        $this->facetCollection->addFacet($facet);

        return $this;
    }

    public function addFacets(array $facets): self
    {
        foreach ($facets as $facet) {
            $this->addFacet($facet);
        }

        return $this;
    }

    public function getFilter(): Criteria
    {
        return $this->criteria;
    }

    /**
     * @param array $filters [string $target, mixed $value, string $comparison] expected for each clause
     * @throws Exception
     */
    public function filterAny(array $filters): self
    {
        $criteria = Criteria::createAny();

        foreach ($filters as $filter) {
            if ($filter instanceof Clause) {
                $criteria->addClause($filter);

                continue;
            }

            $criterion = $this->getCriterionForFilterParams(...$filter);
            $criteria->addClause($criterion);
        }

        $this->criteria->addClause($criteria);

        return $this;
    }

    /**
     * @throws Exception
     */
    public function filter(Clause|string $targetOrClause, mixed $value = null, ?string $comparison = null): self
    {
        $this->criteria->filter($targetOrClause, $value, $comparison);

        return $this;
    }

    /**
     * @param string $direction use the SORT_ constants defined in this class
     * @throws Exception
     */
    public function addSort(string $fieldName, string $direction = self::SORT_ASC): self
    {
        if (!in_array($direction, self::SORTS)) {
            throw new Exception(sprintf('Invalid sort $direction "%s"', $direction));
        }

        $this->sort[$fieldName] = $direction;

        return $this;
    }

    /**
     * Adds multiple sort methods at once
     *
     * @param array $sortMethods [$fieldName => $direction]
     * @throws Exception
     */
    public function addSorts(array $sortMethods): self
    {
        foreach ($sortMethods as $fieldName => $direction) {
            $this->addSort($fieldName, $direction);
        }

        return $this;
    }

    public function getSort(): array
    {
        return $this->sort;
    }

    /**
     * @return ResultField[]
     */
    public function getResultFields(): array
    {
        return $this->resultFields;
    }

    public function addResultField(string $fieldName, int $length = 0, bool $formatted = false): self
    {
        $resultField = ResultField::create($fieldName, $length, $formatted);

        $this->resultFields[] = $resultField;

        return $this;
    }

    public function getSearchFields(): array
    {
        return $this->searchFields;
    }

    public function addSearchField(string $field, int $weight = 0): self
    {
        $this->searchFields[$field] = $weight;

        return $this;
    }

    /**
     * @param array $searchFields An array of field names, or an associative array of $fieldName => $weight, or a mix
     * of both. Basically, if the key is an int, we'll assume the value is the fieldName, and you didn't want a weight
     * for that fieldName
     */
    public function addSearchFields(array $searchFields): self
    {
        foreach ($searchFields as $key => $value) {
            if (is_int($key)) {
                $fieldName = $value;
                $weight = 0;
            } else {
                $fieldName = $key;
                $weight = $value ?? 0;
            }

            $this->addSearchField($fieldName, $weight);
        }

        return $this;
    }

    public function setPageNum(int $pageNum): self
    {
        $this->pageNum = $pageNum;

        return $this;
    }

    public function getPageNum(): ?int
    {
        return $this->pageNum;
    }

    public function setPageSize(int $pageSize): self
    {
        $this->pageSize = $pageSize;

        return $this;
    }

    public function getPageSize(): ?int
    {
        return $this->pageSize;
    }

    public function hasPagination(): bool
    {
        return $this->pageSize || $this->pageNum;
    }

    public function setPagination(int $pageSize, int $pageNum): self
    {
        $this->setPageSize($pageSize);
        $this->setPageNum($pageNum);

        return $this;
    }

    public function forTemplate(): string
    {
        return $this->queryString;
    }

    /**
     * @throws Exception
     */
    private function getCriterionForFilterParams(): Criterion
    {
        $argCount = count(func_get_args());

        if ($argCount === 1) {
            $criterion = func_get_arg(0);

            if (!$criterion instanceof Criterion) {
                throw new Exception('Criterion class expected when only 1 argument is provided for a filter');
            }

            return $criterion;
        }

        if ($argCount !== 3) {
            throw new Exception('Incorrect number of arguments provided for filter');
        }

        $target = func_get_arg(0);
        $value = func_get_arg(1);
        $comparison = func_get_arg(2);

        return Criterion::create($target, $value, $comparison);
    }

}
