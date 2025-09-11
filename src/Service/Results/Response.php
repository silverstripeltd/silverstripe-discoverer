<?php

namespace SilverStripe\Discoverer\Service\Results;

use JsonSerializable;
use SilverStripe\Model\ModelData;

abstract class Response extends ModelData implements JsonSerializable
{

    public function __construct(private readonly int $statusCode)
    {
        parent::__construct();
    }

    public function forTemplate(): string
    {
        return $this->renderWith(static::class);
    }

    public function isSuccess(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

}
