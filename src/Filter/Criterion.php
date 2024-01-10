<?php

namespace SilverStripe\Search\Filter;

use Exception;
use SilverStripe\Core\Injector\Injectable;

class Criterion implements Clause
{

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

    use Injectable;

    public ?CriterionAdaptor $adaptor = null;

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
        if ($this->comparison === self::RANGE) {
            $this->validateRange();
        }
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

    public function getPreparedClause(): string
    {
        return $this->adaptor->prepareClause($this);
    }

    /**
     * @throws Exception
     */
    private function validateRange(): void
    {
        if (!is_array($this->value)) {
            throw new Exception('Expected to receive an array with "from" and "to" keys for Range value');
        }

        $from = $this->value['from'] ?? null;
        $to = $this->value['to'] ?? null;

        if (!$from || !$to) {
            throw new Exception('Range comparison value must contain array keys "from" and "to"');
        }
    }

}
