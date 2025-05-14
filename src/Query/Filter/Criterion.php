<?php

namespace SilverStripe\Discoverer\Query\Filter;

use Exception;
use SilverStripe\Core\Injector\Injectable;

/**
 * A Criterion is a single filter clause. EG: field1 = value1, or value1 IN (array, of, values), etc
 *
 * Query DSL docs:
 * https://www.elastic.co/guide/en/elasticsearch/reference/master/query-dsl.html
 */
class Criterion implements Clause
{

    use Injectable;

    public const string EQUAL = 'EQUAL';
    public const string NOT_EQUAL = 'NOT_EQUAL';
    public const string GREATER_EQUAL = 'GREATER_EQUAL';
    public const string GREATER_THAN = 'GREATER_THAN';
    public const string LESS_EQUAL = 'LESS_EQUAL';
    public const string LESS_THAN = 'LESS_THAN';
    public const string IN = 'IN';
    public const string NOT_IN = 'NOT_IN';
    public const string IS_NULL = 'IS_NULL';
    public const string IS_NOT_NULL = 'IS_NOT_NULL';
    public const string RANGE = 'RANGE';

    public const array COMPARISONS = [
        self::EQUAL,
        self::NOT_EQUAL,
        self::GREATER_EQUAL,
        self::GREATER_THAN,
        self::LESS_EQUAL,
        self::LESS_THAN,
        self::IN,
        self::NOT_IN,
        self::IS_NULL,
        self::IS_NOT_NULL,
        self::RANGE,
    ];

    public const array NULL_COMPARISONS = [
        self::IS_NULL,
        self::IS_NOT_NULL,
    ];

    private ?CriterionAdaptor $adaptor = null;

    private static array $dependencies = [
        'adaptor' => '%$' . CriterionAdaptor::class,
    ];

    /**
     * @throws Exception
     */
    public function __construct(
        private readonly string $target,
        private readonly mixed $value,
        private readonly string $comparison
    ) {
        if (!in_array($this->comparison, self::COMPARISONS, true)) {
            throw new Exception(sprintf('Invalid comparison provided: "%s"', $this->comparison));
        }

        switch ($this->comparison) {
            case self::RANGE:
                $this->validateRange();

                break;

            case self::IN:
            case self::NOT_IN:
                $this->validateIn();

                break;
        }
    }

    public function setAdaptor(?CriterionAdaptor $adaptor): void
    {
        $this->adaptor = $adaptor;
    }

    public function getComparison(): ?string
    {
        return $this->comparison;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getPreparedClause(): mixed
    {
        return $this->adaptor?->prepareCriterion($this);
    }

    /**
     * Note: Ranges are considered to be *inclusive* of your "to" and "from" values
     *
     * @throws Exception
     */
    private function validateRange(): void
    {
        if (!is_array($this->value)) {
            throw new Exception('Expected to receive an array with "from" and "to" keys for Range $value');
        }

        $from = $this->value['from'] ?? null;
        $to = $this->value['to'] ?? null;

        if (!$from && !$to) {
            throw new Exception('Range comparison $value must contain one (or both) of keys "from" and "to"');
        }
    }

    /**
     * @throws Exception
     */
    private function validateIn(): void
    {
        if (is_array($this->value)) {
            return;
        }

        throw new Exception('$value of type array expected for IN and NOT_IN comparisons');
    }

}
