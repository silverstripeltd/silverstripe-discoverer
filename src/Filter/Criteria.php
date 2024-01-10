<?php

namespace SilverStripe\Search\Filter;

class Criteria implements Clause
{

    const CONJUNCTION_AND = 'AND';
    const CONJUNCTION_OR = 'OR';

    /**
     * A collection of Criterion and Criteria
     *
     * @var Clause[]
     */
    private array $clauses = [];

    private ?CriteriaWriter $writer = null;

    private static array $dependencies = [
        'writer' => '%$' . CriteriaWriter::class,
    ];

    public function getPreparedClause(): string
    {
        return $this->writer->generateClauseString($this);
    }

}
