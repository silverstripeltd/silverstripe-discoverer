<?php

namespace SilverStripe\Discoverer\Query\Filter;

use Exception;
use SilverStripe\Core\Injector\Injectable;

/**
 * A Criteria is a collection of filter clauses. The collection (in the case of many search services, but not all), can
 * include a mixture of other Criteria (other collections of clauses) and Criterion (individual clauses)
 *
 * The Criteria class will allow you to perform nested filtering, for example, if you need to achieve (something like):
 * (
 *   (field1 = value1 OR field1 = value2)
 *   AND
 *   (field2 = value3 OR field2 = value4)
 * )
 */
class Criteria implements Clause
{

    use Injectable;

    public const string CONJUNCTION_AND = 'AND';
    public const string CONJUNCTION_OR = 'OR';

    public const array CONJUNCTIONS = [
        self::CONJUNCTION_AND,
        self::CONJUNCTION_OR,
    ];

    private ?CriteriaAdaptor $adaptor = null;

    /**
     * A collection of Criteria and Criterion
     *
     * @var Clause[]
     */
    private array $clauses = [];

    private static array $dependencies = [
        'adaptor' => '%$' . CriteriaAdaptor::class,
    ];

    /**
     * @throws Exception
     */
    public function __construct(private readonly string $conjunction)
    {
        if (!in_array($conjunction, self::CONJUNCTIONS, true)) {
            throw new Exception(sprintf('Invalid conjunction provided "%s"', $conjunction));
        }
    }

    public static function createAny(): static
    {
        return static::create(self::CONJUNCTION_OR);
    }

    public static function createAll(): static
    {
        return static::create(self::CONJUNCTION_AND);
    }

    public function setAdaptor(?CriteriaAdaptor $adaptor): void
    {
        $this->adaptor = $adaptor;
    }

    public function getPreparedClause(): mixed
    {
        return $this->adaptor?->prepareCriteria($this);
    }

    public function getConjunction(): string
    {
        return $this->conjunction;
    }

    public function addClause(Clause $clause): static
    {
        $this->clauses[] = $clause;

        return $this;
    }

    public function getClauses(): array
    {
        return $this->clauses;
    }

    /**
     * @throws Exception
     */
    public function filter(Clause|string $targetOrClause, mixed $value = null, ?string $comparison = null): static
    {
        if ($targetOrClause instanceof Clause) {
            $this->addClause($targetOrClause);

            return $this;
        }

        // Only NULL_COMPARISONS support a $value of null. All other falsy values are allowed
        if ($value === null && !in_array($comparison, Criterion::NULL_COMPARISONS, true)) {
            throw new Exception('mixed $value of null is not supported outside of NULL_COMPARISON filters');
        }

        if (!$comparison) {
            throw new Exception('string $comparison expected for filter()');
        }

        $clause = Criterion::create($targetOrClause, $value, $comparison);
        $this->addClause($clause);

        return $this;
    }

}
