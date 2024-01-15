<?php

namespace SilverStripe\Search\Query;

use SilverStripe\View\ViewableData;

/**
 * Class properties need to be public, so that they can be searched within an ArrayList. Getters are also provided, as
 * those still work within SS templates
 */
class Facet extends ViewableData
{

    public ?int $count;

    public mixed $from;

    public ?string $name;

    public ?string $property;

    public mixed $to;

    public mixed $value;

    public function setCount(?int $count): self
    {
        $this->count = $count;

        return $this;
    }

    public function getFrom(): mixed
    {
        return $this->from;
    }

    public function setFrom(mixed $from): self
    {
        $this->from = $from;

        return $this;
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

    public function getTo(): mixed
    {
        return $this->to;
    }

    public function setTo(mixed $to): self
    {
        $this->to = $to;

        return $this;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): self
    {
        $this->value = $value;

        return $this;
    }

}
