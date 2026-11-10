<?php

namespace SilverStripe\Discoverer\Query;

use Exception;
use InvalidArgumentException;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Discoverer\Query\Boost\Boost;
use SilverStripe\Discoverer\Query\Boost\BoostCollection;
use SilverStripe\Discoverer\Query\Facet\Facet;
use SilverStripe\Discoverer\Query\Facet\FacetCollection;
use SilverStripe\Discoverer\Query\Filter\Clause;
use SilverStripe\Discoverer\Query\Filter\Criteria;
use SilverStripe\Discoverer\Query\Filter\Criterion;

class Query
{

    use Injectable;

    public const string SORT_ASC = 'ASC';
    public const string SORT_DESC = 'DESC';

    public const array SORTS = [
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

    private ?int $paginationOffset = null;

    private ?int $paginationLimit = null;

    private array $resultFields = [];

    private array $searchFields = [];

    private array $sort = [];

    private array $tags = [];

    private ?int $precision = null;

    /**
     * The query-time boosts, translated for the provider by its `BoostAdaptor`.
     */
    private BoostCollection $boostCollection;

    public function __construct(private string $queryString = '')
    {
        // See the docblock on self::$filter for some details
        $this->criteria = Criteria::createAll();
        $this->facetCollection = FacetCollection::create();
        $this->boostCollection = BoostCollection::create();
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

    public function addFacet(Facet $facet): static
    {
        $this->facetCollection->addFacet($facet);

        return $this;
    }

    public function addFacets(array $facets): static
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
    public function filterAny(array $filters): static
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
    public function filter(Clause|string $targetOrClause, mixed $value = null, ?string $comparison = null): static
    {
        $this->criteria->filter($targetOrClause, $value, $comparison);

        return $this;
    }

    /**
     * @param string $direction use the SORT_ constants defined in this class
     * @throws Exception
     */
    public function addSort(string $fieldName, string $direction = self::SORT_ASC): static
    {
        if (!in_array($direction, self::SORTS, true)) {
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
    public function addSorts(array $sortMethods): static
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

    public function addTag(string $tag): static
    {
        $this->tags[] = $tag;

        return $this;
    }

    public function setTags(array $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * Override the engine's configured precision for this query only. Not all search services support precision tuning;
     * where unsupported, this value is ignored. When null, the engine's configured precision is used.
     *
     * @param int|null $precision A precision level, or null to defer to the engine's configured precision
     */
    public function setPrecision(?int $precision): static
    {
        $this->precision = $precision;

        return $this;
    }

    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    /**
     * Get the collection holding this query's boosts, for a provider to translate.
     *
     * @return BoostCollection - the boosts on this query
     */
    public function getBoostCollection(): BoostCollection
    {
        return $this->boostCollection;
    }

    /**
     * Add a query-time relevance boost for a field. Several boosts on one field are kept in the order they were added.
     * Not all search services support query-time boosts; where unsupported, boosts are ignored. Some providers (the
     * Silverstripe Search Service among them) replace the engine's stored boosts for the query when query-time
     * boosts are sent.
     *
     * @param string $field The name of the field to boost
     * @param Boost $boost The boost to apply to the field
     * @return static - this query, for chaining
     * @throws InvalidArgumentException When the field name is empty, or the provider's adaptor rejects the boost
     */
    public function addBoost(string $field, Boost $boost): static
    {
        $this->boostCollection->addBoost($field, $boost);

        return $this;
    }

    /**
     * Replace all boosts, or none of them when any entry is invalid. Pass an empty array to clear them; on providers
     * where query-time boosts replace the engine's stored boosts, clearing restores the stored boosts.
     *
     * @param array<string, Boost[]> $boosts The boosts keyed by field name, in the shape `getBoosts()` returns
     * @return static - this query, for chaining
     * @throws InvalidArgumentException When a field name is empty, a field's entry is not an array, an element is
     *     not a `Boost`, or the provider's adaptor rejects a boost
     */
    public function setBoosts(array $boosts): static
    {
        $this->boostCollection->setBoosts($boosts);

        return $this;
    }

    /**
     * Get all boosts.
     *
     * @return array<int|string, Boost[]> - the boosts keyed by field name, each holding a list in insertion order;
     *     numeric field names come back as `int` keys, so adaptors must cast them to `string`
     */
    public function getBoosts(): array
    {
        return $this->boostCollection->getBoosts();
    }

    /**
     * @return ResultField[]
     */
    public function getResultFields(): array
    {
        return $this->resultFields;
    }

    public function addResultField(string $fieldName, int $length = 0, bool $formatted = false): static
    {
        $resultField = ResultField::create($fieldName, $length, $formatted);

        $this->resultFields[] = $resultField;

        return $this;
    }

    public function getSearchFields(): array
    {
        return $this->searchFields;
    }

    public function addSearchField(string $field, int $weight = 0): static
    {
        $this->searchFields[$field] = $weight;

        return $this;
    }

    /**
     * @param array $searchFields An array of field names, or an associative array of $fieldName => $weight, or a mix
     * of both. Basically, if the key is an int, we'll assume the value is the fieldName, and you didn't want a weight
     * for that fieldName
     */
    public function addSearchFields(array $searchFields): static
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

    public function setPaginationOffset(int $paginationOffset): static
    {
        $this->paginationOffset = $paginationOffset;

        return $this;
    }

    public function getPaginationOffset(): ?int
    {
        return $this->paginationOffset;
    }

    public function setPaginationLimit(int $paginationLimit): static
    {
        $this->paginationLimit = $paginationLimit;

        return $this;
    }

    public function getPaginationLimit(): ?int
    {
        return $this->paginationLimit;
    }

    public function hasPagination(): bool
    {
        return $this->paginationLimit !== null && $this->paginationOffset !== null;
    }

    public function setPagination(int $limit, int $offset): static
    {
        $this->setPaginationLimit($limit);
        $this->setPaginationOffset($offset);

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
