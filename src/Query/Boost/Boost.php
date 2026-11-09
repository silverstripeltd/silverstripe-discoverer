<?php

namespace SilverStripe\Discoverer\Query\Boost;

use InvalidArgumentException;
use SilverStripe\Core\Injector\Injectable;

/**
 * A provider-neutral query-time relevance boost for a single field.
 *
 * Each subclass is one kind of boost and holds only its own state; this base class holds the boost strength and
 * the named constructors. Boosts are immutable definitions added to a `Query`, and a `BoostAdaptor` translates them
 * into the provider's request format.
 */
abstract class Boost
{

    use Injectable;

    /**
     * The boost strength as a float, or `null` to use the provider default.
     */
    private readonly ?float $factor;

    /**
     * Build a boost with the given strength.
     *
     * @param int|float|null $factor the boost strength, `null` to use the provider default
     * @throws InvalidArgumentException When the factor is negative or not finite
     */
    public function __construct(int|float|null $factor = null)
    {
        if ($factor !== null && (!is_finite($factor) || $factor < 0)) {
            throw new InvalidArgumentException(
                sprintf('Boost $factor must be a finite number that is not negative, got %s', $factor)
            );
        }

        $this->factor = $factor === null ? null : (float) $factor;
    }

    /**
     * Boost documents whose field matches any of the given values.
     *
     * @param array<int, string|int|float> $values pass the value as it was indexed; booleans are rejected
     * @param int|float|null $factor the boost strength, `null` to use the provider default
     * @return ValueBoost - the new value boost
     * @throws InvalidArgumentException When the values or factor are invalid
     */
    public static function value(array $values, int|float|null $factor = null): ValueBoost
    {
        return ValueBoost::create($values, $factor);
    }

    /**
     * Boost documents whose field is close to the centre value.
     *
     * @param string|int|float $center the value to measure proximity from (number, date or location)
     * @param string $function use the `FUNCTION_` constants defined in `ProximityBoost`
     * @param int|float|null $factor the boost strength, `null` to use the provider default
     * @return ProximityBoost - the new proximity boost
     * @throws InvalidArgumentException When the centre, function or factor are invalid
     */
    public static function proximity(
        string|int|float $center,
        string $function,
        int|float|null $factor = null
    ): ProximityBoost {
        return ProximityBoost::create($center, $function, $factor);
    }

    /**
     * Get the boost strength.
     *
     * @return float|null - the factor, `null` when the provider default applies
     */
    public function getFactor(): ?float
    {
        return $this->factor;
    }

}
