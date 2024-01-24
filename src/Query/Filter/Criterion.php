<?php

namespace SilverStripe\Discoverer\Query\Filter;

use Exception;
use SilverStripe\Core\Injector\Injectable;

/**
 * A Criterion is a single filter clause. EG: field1 = value1, or value1 IN (array, of, values), etc
 */
class Criterion implements Clause
{

    use Injectable;

    public const EQUAL = 'EQUAL';
    public const NOT_EQUAL = 'NOT_EQUAL';
    public const GREATER_EQUAL = 'GREATER_EQUAL';
    public const GREATER_THAN = 'GREATER_THAN';
    public const LESS_EQUAL = 'LESS_EQUAL';
    public const LESS_THAN = 'LESS_THAN';
    public const IN = 'IN';
    public const NOT_IN = 'NOT_IN';
    public const IS_NULL = 'IS_NULL';
    public const IS_NOT_NULL = 'IS_NOT_NULL';
    public const RANGE = 'RANGE';

    public const COMPARISONS = [
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
        if (!in_array($this->comparison, self::COMPARISONS)) {
            throw new Exception(sprintf('Invalid comparison provided: "%s"', $this->comparison));
        }

        if ($this->comparison === self::RANGE) {
            $this->validateRange();
        }

        if ($this->comparison === self::IN || $this->comparison === self::NOT_IN) {
            $this->validateIn();
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
