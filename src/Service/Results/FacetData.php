<?php

namespace SilverStripe\Discoverer\Service\Results;

use JsonSerializable;
use SilverStripe\Model\ModelData;

class FacetData extends ModelData implements JsonSerializable
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

    public function setCount(int $count): static
    {
        $this->count = $count;

        return $this;
    }

    public function getFrom(): float|int|string
    {
        return $this->from;
    }

    public function setFrom(float|int|string $from): static
    {
        $this->from = $from;

        return $this;
    }

    public function getTo(): float|int|string
    {
        return $this->to;
    }

    public function setTo(float|int|string $to): static
    {
        $this->to = $to;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getValue(): string|int|float
    {
        return $this->value;
    }

    public function setValue(string|int|float $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'count' => $this->getCount(),
            'from' => $this->getFrom(),
            'to' => $this->getTo(),
            'name' => $this->getName(),
            'value' => $this->getValue(),
        ];
    }

}
