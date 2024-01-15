<?php

namespace SilverStripe\Search\Service\Result;

use Psr\Http\Message\ResponseInterface;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\Search\Query\Facet\Facet;
use SilverStripe\Search\Query\Query;
use SilverStripe\View\ViewableData;

class Results
{

    use Injectable;

    /**
     * @var PaginatedList|ViewableData[]|null
     */
    private ?PaginatedList $results = null;

    /**
     * @var ArrayList|Facet[]|null $facets
     */
    private ?ArrayList $facets = null;

    private ?string $indexName = null;

    public function __construct(private readonly Query $query, private readonly ResponseInterface $response)
    {
    }

    public function getIndexName(): ?string
    {
        return $this->indexName;
    }

    public function setIndexName(?string $indexName): self
    {
        $this->indexName = $indexName;

        return $this;
    }

}
