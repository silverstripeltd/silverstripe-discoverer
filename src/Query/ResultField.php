<?php

namespace SilverStripe\Search\Query;

use SilverStripe\Core\Injector\Injectable;

class ResultField
{

    use Injectable;

    public function __construct(
        private readonly string $fieldName,
        private readonly int $length,
        private readonly bool $formatted
    ) {}

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
