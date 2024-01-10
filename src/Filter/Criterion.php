<?php

namespace SilverStripe\Search\Filter;

use SilverStripe\Core\Injector\Injectable;

class Criterion implements Clause
{

    const EQUAL = 'EQUAL';
    const NOT_EQUAL = 'NOT_EQUAL';
    const GREATER_EQUAL = 'GREATER_EQUAL';
    const GREATER_THAN = 'GREATER_THAN';
    const LESS_EQUAL = 'LESS_EQUAL';
    const LESS_THAN = 'LESS_THAN';
    const IN = 'IN';
    const NOT_IN = 'NOT_IN';
    const IS_NULL = 'ISNULL';
    const IS_NOT_NULL = 'ISNOTNULL';

    use Injectable;

    private ?CriterionWriter $writer = null;

    private static array $dependencies = [
        'writer' => '%$' . CriterionWriter::class,
    ];

    public function __construct(
        private readonly string $target,
        private readonly mixed $value,
        private readonly string $comparison
    ) {
    }

    public function getComparison(): ?string
    {
        return '';
    }

    public function getTarget(): ?string
    {
        return '';
    }

    public function getValue(): mixed
    {
        return '';
    }

    public function getPreparedClause(): string
    {
        return $this->writer->generateClauseString($this);
    }

}
