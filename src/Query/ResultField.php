<?php

namespace SilverStripe\Discoverer\Query;

use SilverStripe\Core\Injector\Injectable;

class ResultField
{

    use Injectable;

    public function __construct(
        private readonly string $fieldName,
        private readonly int $length = 0,
        private readonly bool $formatted = false
    ) {
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function isFormatted(): bool
    {
        return $this->formatted;
    }

}
