<?php

namespace SilverStripe\Discoverer\Service\Results;

use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\PaginatedList;

class Records extends PaginatedList
{

    public function forTemplate(): DBHTMLText
    {
        return $this->renderWith(static::class);
    }

}
