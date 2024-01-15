<?php

namespace SilverStripe\Search\Service\Facet;

use SilverStripe\View\ViewableData;

class Facet extends ViewableData
{

    /**
     * @var FacetData[]
     */
    private array $data = [];

    public function __construct(private readonly string $type, private readonly string $name)
    {
        parent::__construct();
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function addData(FacetData $facetData): void
    {
        $this->data[] = $facetData;
    }

}
