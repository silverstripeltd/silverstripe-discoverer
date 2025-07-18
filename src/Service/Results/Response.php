<?php

namespace SilverStripe\Discoverer\Service\Results;

use JsonSerializable;
use SilverStripe\Model\ModelData;

abstract class Response extends ModelData implements JsonSerializable
{

    private bool $success = false;

    public function forTemplate(): string
    {
        return $this->renderWith(static::class);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): static
    {
        $this->success = $success;

        return $this;
    }

}
