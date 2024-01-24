<?php

namespace SilverStripe\Discoverer\Query\Filter;

/**
 * The idea behind a Clause is that Criteria (collections) and Criterion (single comparisons) can be mixed
 * together in any shape to form your filter requirements
 */
interface Clause
{

    public function getPreparedClause(): mixed;

}
