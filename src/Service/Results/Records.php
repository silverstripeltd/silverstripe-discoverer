<?php

namespace SilverStripe\Discoverer\Service\Results;

use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\List\PaginatedList;

/**
 * @extends PaginatedList<ArrayList<Record>, Record>
 */
class Records extends PaginatedList
{

    public function forTemplate(): string
    {
        return $this->renderWith(static::class);
    }

}
