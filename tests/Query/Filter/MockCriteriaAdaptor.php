<?php

namespace SilverStripe\Discoverer\Tests\Query\Filter;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Discoverer\Query\Filter\Criteria;
use SilverStripe\Discoverer\Query\Filter\CriteriaAdaptor;

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
