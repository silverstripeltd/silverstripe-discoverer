<?php

namespace SilverStripe\Discoverer\Service\Results;

use SilverStripe\View\ViewableData;

class FacetData extends ViewableData
{

    private int $count;

    private string|int|float $from;

    private string|int|float $to;

    private string $name;

    private string|int|float $value;

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): self
    {
        $this->count = $count;

        return $this;
    }

    public function getFrom(): float|int|string
    {
        return $this->from;
    }

    public function setFrom(float|int|string $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function getTo(): float|int|string
    {
        return $this->to;
    }

    public function setTo(float|int|string $to): self
    {
        $this->to = $to;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getValue(): string|int|float
    {
        return $this->value;
    }

    public function setValue(string|int|float $value): self
    {
        $this->value = $value;

        return $this;
    }

}
