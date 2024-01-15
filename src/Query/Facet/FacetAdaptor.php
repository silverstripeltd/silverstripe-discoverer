<?php

namespace SilverStripe\Search\Query\Facet;

interface FacetAdaptor
{

    public function prepareFacets(FacetCollection $facetCollection): mixed;

}
