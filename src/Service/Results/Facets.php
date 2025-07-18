<?php

namespace SilverStripe\Discoverer\Service\Results;

use JsonSerializable;
use SilverStripe\Model\List\ArrayList;

/**
 * @extends ArrayList<Facet>
 */
class Facets extends ArrayList implements JsonSerializable
{

    public function forTemplate(): string
    {
        return $this->renderWith(static::class);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

}
