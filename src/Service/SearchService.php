<?php

namespace SilverStripe\Search\Service;

use SilverStripe\Search\Query\Result;

interface SearchService
{

    public function search(): Result;

}
