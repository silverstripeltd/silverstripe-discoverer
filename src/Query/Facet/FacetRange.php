<?php

namespace SilverStripe\Discoverer\Query\Facet;

use SilverStripe\Core\Injector\Injectable;

class FacetRange
{

    use Injectable;

    public function __construct(
        private string|int|float|null $from = null,
        private string|int|float|null $to = null,
        private ?string $name = null
    ) {
    }

    public function getFrom(): float|int|string|null
    {
        return $this->from;
    }

    public function setFrom(float|int|string|null $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function getTo(): float|int|string|null
    {
        return $this->to;
    }

    public function setTo(float|int|string|null $to): self
    {
        $this->to = $to;

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

}
