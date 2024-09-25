<?php

namespace SilverStripe\Discoverer\Service;

use SilverStripe\Core\Injector\Injectable;

class FieldService
{

    use Injectable;

    public function getConvertedFieldName(string $fieldName): string
    {
        $fieldName = str_replace('_', '', ucwords($fieldName, '_'));

        return str_replace('-', '', ucwords($fieldName, '-'));
    }

}
