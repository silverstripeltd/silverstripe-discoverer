<?php

namespace SilverStripe\Discoverer\Service\Results;

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\ViewableData;

class Facet extends ViewableData
{

    /**
     * @var ArrayList|FacetData[]
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

    public function forTemplate(): DBHTMLText
    {
        return $this->renderWith(static::class);
    }

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
