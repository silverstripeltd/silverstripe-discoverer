<?php

namespace SilverStripe\Discoverer\Service\Results;

use SilverStripe\View\ViewableData;

class Field extends ViewableData
{

    public function __construct(private mixed $raw = null, private mixed $formatted = null)
    {
        parent::__construct();
    }

    public function forTemplate(): mixed
    {
        // Attempt to return the formatted value (if available), or return raw as the fallback
        return $this->formatted ?? $this->raw;
    }

    public function getRaw(): mixed
    {
        return $this->raw;
    }

    public function setRaw(mixed $raw): self
    {
        $this->raw = $raw;

        return $this;
    }

    public function getFormatted(): mixed
    {
        return $this->formatted;
    }

    public function setFormatted(mixed $formatted): self
    {
        $this->formatted = $formatted;

        return $this;
    }

}
