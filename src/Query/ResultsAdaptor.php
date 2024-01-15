<?php

namespace SilverStripe\Search\Query;

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\PaginatedList;

interface ResultsAdaptor
{

    public function extractResults(): PaginatedList;

    public function extractFacets(): ArrayList;

}
