<?php

namespace SilverStripe\Discoverer\Query\Facet;

use SilverStripe\Core\Injector\Injectable;

class FacetCollection
{

    use Injectable;

    private ?FacetAdaptor $adaptor = null;

    /**
     * @var Facet[]
     */
    private array $facets = [];

    private static array $dependencies = [
        'adaptor' => '%$' . FacetAdaptor::class,
    ];

    public function setAdaptor(?FacetAdaptor $adaptor): void
    {
        $this->adaptor = $adaptor;
    }

    public function getFacets(): array
    {
        return $this->facets;
    }

    public function addFacet(Facet $facet): self
    {
        $this->facets[] = $facet;

        return $this;
    }

    public function getPreparedFacets(): mixed
    {
        return $this->adaptor->prepareFacets($this);
    }

}
