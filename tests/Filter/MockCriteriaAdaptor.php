<?php

namespace SilverStripe\Search\Tests\Filter;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Search\Filter\Criteria;
use SilverStripe\Search\Filter\CriteriaAdaptor;

class MockCriteriaAdaptor implements TestOnly, CriteriaAdaptor
{

    use Injectable;

    public function prepareClause(Criteria $criteria): string
    {
        $preparedClauses = [];

        foreach ($criteria->getClauses() as $clause) {
            $preparedClauses[] = $clause->getPreparedClause();
        }

        return sprintf('(%s)', implode(sprintf(' %s ', $criteria->getConjunction()), $preparedClauses));
    }

}
