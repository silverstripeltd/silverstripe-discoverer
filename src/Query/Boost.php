<?php

namespace SilverStripe\Discoverer\Query;

use InvalidArgumentException;
use SilverStripe\Core\Injector\Injectable;

/**
 * A provider-neutral query-time relevance boost for a single field.
 *
 * A boost is either a value boost (documents whose field matches one of a list of values) or a proximity boost
 * (documents whose field is close to a centre value). It is an immutable definition that is passed into a Query.
 * Provider adaptors translate it into their own request format.
 */
class Boost
{

    use Injectable;

    public const string TYPE_VALUE = 'value';
    public const string TYPE_PROXIMITY = 'proximity';

    public const string FUNCTION_GAUSSIAN = 'gaussian';
    public const string FUNCTION_EXPONENTIAL = 'exponential';
    public const string FUNCTION_LINEAR = 'linear';

    public const array FUNCTIONS = [
        self::FUNCTION_GAUSSIAN,
        self::FUNCTION_EXPONENTIAL,
        self::FUNCTION_LINEAR,
    ];

    private readonly ?float $factor;

    /**
     * @var list<string|int|float>
     */
    private readonly array $values;

    /**
     * @param string $type use the TYPE_ constants defined in this class
     * @param array<int, string|int|float> $values the field values to boost, for a value boost
     * @param string|int|float|null $center the value to measure proximity from, for a proximity boost
     * @param string|null $function use the FUNCTION_ constants defined in this class, for a proximity boost
     * @param int|float|null $factor the boost strength, null to use the provider default
     * @throws InvalidArgumentException
     */
    public function __construct(
        private readonly string $type,
        array $values = [],
        private readonly string|int|float|null $center = null,
        private readonly ?string $function = null,
        int|float|null $factor = null
    ) {
        if ($factor !== null && (!is_finite($factor) || $factor < 0)) {
            throw new InvalidArgumentException(
                sprintf('Boost $factor must be a finite number that is not negative, got %s', $factor)
            );
        }

        // Filtered arrays keep their gaps, which would serialise as a JSON object rather than a list
        $this->values = array_values($values);

        $this->factor = $factor === null ? null : (float) $factor;

        if ($type === self::TYPE_VALUE) {
            $this->validateValues();
        } elseif ($type === self::TYPE_PROXIMITY) {
            $this->validateProximity();
        } else {
            throw new InvalidArgumentException(sprintf('Invalid boost $type "%s"', $type));
        }
    }

    /**
     * Boost documents whose field matches any of the given values.
     *
     * @param array<int, string|int|float> $values pass the value as it was indexed; booleans are rejected
     * @param int|float|null $factor the boost strength, null to use the provider default
     * @throws InvalidArgumentException
     */
    public static function value(array $values, int|float|null $factor = null): static
    {
        return static::create(self::TYPE_VALUE, $values, null, null, $factor);
    }

    /**
     * Boost documents whose field is close to the centre value.
     *
     * @param string|int|float $center the value to measure proximity from (number, date or location)
     * @param string $function use the FUNCTION_ constants defined in this class
     * @param int|float|null $factor the boost strength, null to use the provider default
     * @throws InvalidArgumentException
     */
    public static function proximity(string|int|float $center, string $function, int|float|null $factor = null): static
    {
        return static::create(self::TYPE_PROXIMITY, [], $center, $function, $factor);
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return list<string|int|float>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function getCenter(): string|int|float|null
    {
        return $this->center;
    }

    public function getFunction(): ?string
    {
        return $this->function;
    }

    public function getFactor(): ?float
    {
        return $this->factor;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateValues(): void
    {
        if ($this->values === []) {
            throw new InvalidArgumentException('A value boost requires at least one value');
        }

        foreach ($this->values as $value) {
            // PHP casts true to '1', which never matches a stored "true"
            if (!is_string($value) && !is_int($value) && !is_float($value)) {
                throw new InvalidArgumentException(
                    sprintf('Boost values must be string, int or float, got %s', get_debug_type($value))
                );
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateProximity(): void
    {
        if ($this->center === null || $this->center === '') {
            throw new InvalidArgumentException('A proximity boost requires a center');
        }

        if (!in_array($this->function, self::FUNCTIONS, true)) {
            throw new InvalidArgumentException(sprintf('Invalid boost $function "%s"', (string) $this->function));
        }
    }

}
