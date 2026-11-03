<?php

declare(strict_types=1);

namespace SilverStripe\Discoverer\Tests\Service\Blocklist;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Discoverer\Query\Query;
use SilverStripe\Discoverer\Service\Interfaces\SearchAdaptor;
use SilverStripe\Discoverer\Service\Results\Results;

/**
 * Counts searches it is asked to process, instead of sending them to a search service
 */
class SpySearchAdaptor implements SearchAdaptor, TestOnly
{

    public int $calls = 0;

    public function process(Query $query, string $indexSuffix): Results
    {
        $this->calls++;

        return Results::create(200, $query);
    }

}
