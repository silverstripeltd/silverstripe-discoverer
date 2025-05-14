<?php

namespace SilverStripe\Discoverer\Service\Results;

use ArrayIterator;
use SilverStripe\Model\ModelData;
use Traversable;

class Field extends ModelData
{

    public function __construct(private mixed $raw = null, private mixed $formatted = null)
    {
        parent::__construct();
    }

    public function forTemplate(): string
    {
        // Attempt to use the formatted value (if available), or use the raw value as the fallback
        return $this->formatted ?? $this->raw;
    }

    public function getRaw(): mixed
    {
        return $this->raw;
    }

    public function setRaw(mixed $raw): static
    {
        $this->raw = $raw;

        return $this;
    }

    public function getFormatted(): mixed
    {
        return $this->formatted;
    }

    public function setFormatted(mixed $formatted): static
    {
        $this->formatted = $formatted;

        return $this;
    }

    public function getIterator(): Traversable
    {
        // Attempt to use the formatted value (if available), or use the raw value as the fallback
        $value = $this->formatted ?? $this->raw;

        if (!$value) {
            return new ArrayIterator();
        }

        $arrayValue = is_array($value)
            ? $this->convertArrayForTemplate($value)
            : [$this];

        return new ArrayIterator($arrayValue);
    }

    /**
     * Silverstripe 5.3 will have native support for looping primitives in templates:
     * https://github.com/silverstripe/silverstripe-framework/issues/11196
     *
     * We need to support versions of Silverstripe below 5.3 though, so we need this polyfill. It emulates the
     * template implementation method from Silverstripe 5.3, so we should be able to remove this later without
     * negatively impacting any project's template implementation
     */
    private function convertArrayForTemplate(array $array): array
    {
        $arrayList = [];

        foreach ($array as $item) {
            // Each item being looped needs to be a ModelData record. Reusing this class means that we also get
            // support for any nested arrays
            $arrayList[] = Field::create($item);
        }

        return $arrayList;
    }

}
