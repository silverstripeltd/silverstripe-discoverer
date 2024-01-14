<?php

namespace SilverStripe\Search\Filter;

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

    public const CONJUNCTION_AND = 'AND';
    public const CONJUNCTION_OR = 'OR';

    public const CONJUNCTIONS = [
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

    private string $conjunction;

    private static array $dependencies = [
        'adaptor' => '%$' . CriteriaAdaptor::class,
    ];

    public function setAdaptor(?CriteriaAdaptor $adaptor): void
    {
        $this->adaptor = $adaptor;
    }

    public static function createAny(): self
    {
        return static::create(self::CONJUNCTION_OR);
    }

    public static function createAll(): self
    {
        return static::create(self::CONJUNCTION_AND);
    }

    /**
     * @throws Exception
     */
    public function __construct(string $conjunction)
    {
        if (!in_array($conjunction, self::CONJUNCTIONS)) {
            throw new Exception(sprintf('Invalid conjunction provided "%s"', $conjunction));
        }

        $this->conjunction = $conjunction;
    }

    public function getPreparedClause(): mixed
    {
        return $this->adaptor?->prepareCriteria($this);
    }

    public function getConjunction(): string
    {
        return $this->conjunction;
    }

    public function addClause(Clause $clause): self
    {
        $this->clauses[] = $clause;

        return $this;
    }

    public function getClauses(): array
    {
        return $this->clauses;
    }

}
