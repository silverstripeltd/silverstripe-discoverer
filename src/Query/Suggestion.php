<?php

namespace SilverStripe\Discoverer\Query;

use SilverStripe\Core\Injector\Injectable;

class Suggestion
{

    use Injectable;

    public function __construct(private string $queryString, private ?int $limit = null, private array $fields = [])
    {
    }

    public function getQueryString(): string
    {
        return $this->queryString;
    }

    public function setQueryString(string $queryString): void
    {
        $this->queryString = $queryString;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function setFields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function addField(string $fieldName): self
    {
        $this->fields[] = $fieldName;

        return $this;
    }

}
