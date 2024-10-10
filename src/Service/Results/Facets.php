<?php

namespace SilverStripe\Discoverer\Service\Results;

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * @extends ArrayList<Facet>
 */
class Facets extends ArrayList
{

    public function forTemplate(): DBHTMLText
    {
        return $this->renderWith(static::class);
    }

}
