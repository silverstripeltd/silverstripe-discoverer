<?php

namespace SilverStripe\Discoverer\Service;

use SilverStripe\Core\Injector\Injectable;

class FieldService
{

    use Injectable;

    public function getConvertedFieldName(string $fieldName): string
    {
        // For camelCase and PascalCase we'll want to standardise them to snake_case (though, they will still have
        // capitalisation, which is fine, as we'll handle this later)
        $fieldNameSplit = preg_split('/(?<=[a-z])(?=[A-Z])/x', $fieldName);
        // Glue the words back together with underscore as a separator
        $fieldName = implode('_', $fieldNameSplit);
        // Standardise everything to lowercase before we apply ucwords()
        $fieldName = strtolower($fieldName);

        // Format any snake_case field names
        $fieldName = str_replace('_', '', ucwords($fieldName, '_'));

        return str_replace('-', '', ucwords($fieldName, '-'));
    }

}
