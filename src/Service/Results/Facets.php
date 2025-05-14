<?php

namespace SilverStripe\Discoverer\Service\Results;

use SilverStripe\Model\List\ArrayList;

/**
 * @extends ArrayList<Facet>
 */
class Facets extends ArrayList
{

    public function forTemplate(): string
    {
        return $this->renderWith(static::class);
    }

}
