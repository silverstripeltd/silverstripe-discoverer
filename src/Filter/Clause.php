<?php

namespace SilverStripe\Search\Filter;

interface Clause
{

    public function getPreparedClause(): mixed;

}
