<?php

namespace SilverStripe\Discoverer\Query\Facet;

interface FacetAdaptor
{

    public function prepareFacets(FacetCollection $facetCollection): mixed;

}
