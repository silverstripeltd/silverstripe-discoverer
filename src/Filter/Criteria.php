<?php

namespace SilverStripe\Search\Filter;

use Exception;
use SilverStripe\Core\Injector\Injectable;

class Criteria implements Clause
{

    use Injectable;

    public const CONJUNCTION_AND = 'AND';
    public const CONJUNCTION_OR = 'OR';

    public const CONJUNCTIONS = [
        self::CONJUNCTION_AND,
        self::CONJUNCTION_OR,
    ];

    /**
     * A collection of Criteria and Criterion
     *
     * @var Clause[]
     */
    private array $clauses = [];

    private ?CriteriaAdaptor $writer = null;

    private string $conjunction;

    private static array $dependencies = [
        'writer' => '%$' . CriteriaAdaptor::class,
    ];

    public static function createAny(): self
    {
        return static::create(self::CONJUNCTION_OR);
    }

    public static function createAll(): self
    {
        return static::create(self::CONJUNCTION_AND);
    }

    public function __construct(string $conjunction)
    {
        $this->setConjunction($conjunction);
    }

    public function getPreparedClause(): string
    {
        return $this->writer->generateClauseString($this);
    }

    public function getConjunction(): string
    {
        return $this->conjunction;
    }

    public function setConjunction(string $conjunction): void
    {
        if (!in_array($conjunction, self::CONJUNCTIONS)) {
            throw new Exception('Invalid conjunction provided');
        }

        $this->conjunction = $conjunction;
    }

    public function addClause(Clause $clause): self
    {
        $this->clauses[] = $clause;

        return $this;
    }

}
