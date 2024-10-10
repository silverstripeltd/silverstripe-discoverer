<?php

namespace SilverStripe\Discoverer\Service\Results;

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\PaginatedList;

/**
 * @extends PaginatedList<ArrayList<Record>, Record>
 */
class Records extends PaginatedList
{

    public function forTemplate(): DBHTMLText
    {
        return $this->renderWith(static::class);
    }

}
