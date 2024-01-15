<?php

namespace SilverStripe\Search\Query\Facet;

use SilverStripe\Core\Injector\Injectable;

class FacetLocation
{

    use Injectable;

    public function __construct(
        private readonly ?string $unit = null,
        private readonly ?float $latitude = null,
        private readonly ?float $longitude = null
    ) {
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

}
