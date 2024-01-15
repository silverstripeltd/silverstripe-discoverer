<?php

namespace SilverStripe\Search\Tests\Query\Filter;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Search\Query\Filter\Criteria;
use SilverStripe\Search\Query\Filter\CriteriaAdaptor;

class MockCriteriaAdaptor implements TestOnly, CriteriaAdaptor
{

    use Injectable;

    public function prepareCriteria(Criteria $criteria): string
    {
        $preparedClauses = [];

        foreach ($criteria->getClauses() as $clause) {
            $preparedClauses[] = $clause->getPreparedClause();
        }

        return sprintf('(%s)', implode(sprintf(' %s ', $criteria->getConjunction()), $preparedClauses));
    }

}
