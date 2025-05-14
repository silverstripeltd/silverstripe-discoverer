<?php

namespace SilverStripe\Discoverer\Service\Results;

use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\ModelData;

class Facet extends ModelData
{

    /**
     * @var ArrayList<FacetData>
     */
    private ArrayList $data;

    private ?string $name = null;

    private ?string $fieldName = null;

    private ?string $type = null;

    public function __construct()
    {
        parent::__construct();

        $this->data = ArrayList::create();
    }

    public function forTemplate(): string
    {
        return $this->renderWith(static::class);
    }

    /**
     * @return ArrayList<FacetData>
     */
    public function getData(): ArrayList
    {
        return $this->data;
    }

    public function addData(FacetData $facetData): void
    {
        $this->data->add($facetData);
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

    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }

    public function setFieldName(?string $fieldName): self
    {
        $this->fieldName = $fieldName;

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
