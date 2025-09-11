<?php

namespace SilverStripe\Discoverer\Query\Facet;

use Exception;
use SilverStripe\Core\Injector\Injectable;

/**
 * Class properties need to be public, so that they can be searched within an ArrayList. Getters are also provided, as
 * those still work within SS templates
 */
class Facet
{

    use Injectable;

    public const string TYPE_VALUE = 'VALUE';
    public const string TYPE_RANGE = 'RANGE';

    public const array TYPES = [
        self::TYPE_VALUE,
        self::TYPE_RANGE,
    ];

    private ?int $limit = null;

    private ?string $name = null;

    /**
     * Usually a field_name
     */
    private ?string $fieldName = null;

    /**
     * @var FacetRange[]
     */
    private array $ranges = [];

    private string $type = self::TYPE_VALUE;

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }

    public function setFieldName(?string $fieldName): static
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    public function getRanges(): array
    {
        return $this->ranges;
    }

    public function addRange(
        string|int|float|null $from = null,
        string|int|float|null $to = null,
        ?string $name = null
    ): static {
        // If a range is added, then we'll update the type
        $this->type = self::TYPE_RANGE;
        $range = FacetRange::create($from, $to, $name);

        $this->ranges[] = $range;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @throws Exception
     */
    public function setType(?string $type): static
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new Exception(sprintf('Invalid type "%s" provided', $type));
        }

        $this->type = $type;

        return $this;
    }

}
