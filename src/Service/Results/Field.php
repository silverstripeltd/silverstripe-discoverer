<?php

namespace SilverStripe\Search\Service\Results;

use SilverStripe\View\ViewableData;

class Field extends ViewableData
{

    private mixed $raw = null;

    private mixed $snippet = null;

    public function forTemplate(): mixed
    {
        // Attempt to return the snippet (if available), or return raw as the fallback
        return $this->snippet ?? $this->raw;
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

    public function getSnippet(): mixed
    {
        return $this->snippet;
    }

    public function setSnippet(mixed $snippet): self
    {
        $this->snippet = $snippet;

        return $this;
    }

}
