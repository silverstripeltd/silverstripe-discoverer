<?php

namespace SilverStripe\Search\Service\Result;

use Exception;
use SilverStripe\View\ViewableData;

class Record extends ViewableData
{

    public function __set($property, $value)
    {
        if (!$value instanceof Field) {
            throw new Exception('Field value must be an instance of SilverStripe\\Search\\Service\\Result\\Field');
        }

        parent::__set($property, $value);
    }

}
