<?php

namespace SilverStripe\Discoverer\Service\Results;

use SilverStripe\View\ViewableData;

class Facet extends ViewableData
{

    /**
     * @var FacetData[]
     */
    private array $data = [];

    private ?string $name = null;

    private ?string $property = null;

    private ?string $type = null;

    public function getData(): array
    {
        return $this->data;
    }

    public function addData(FacetData $facetData): void
    {
        $this->data[] = $facetData;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getProperty(): ?string
    {
        return $this->property;
    }

    public function setProperty(?string $property): self
    {
        $this->property = $property;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

}
