<?php

namespace SilverStripe\Discoverer\Query\Boost;

use InvalidArgumentException;

/**
 * A boost that raises documents whose field is close to a centre value.
 *
 * The centre can be a number, a date or a location, passed through for the provider to interpret. Prefer
 * `Boost::proximity()` to build one.
 */
class ProximityBoost extends Boost
{

    /**
     * The proximity function where the boost falls away along a bell curve from the centre.
     */
    public const string FUNCTION_GAUSSIAN = 'GAUSSIAN';

    /**
     * The proximity function where the boost falls away exponentially from the centre.
     */
    public const string FUNCTION_EXPONENTIAL = 'EXPONENTIAL';

    /**
     * The proximity function where the boost falls away in a straight line from the centre.
     */
    public const string FUNCTION_LINEAR = 'LINEAR';

    /**
     * Every proximity function that a proximity boost accepts.
     */
    public const array FUNCTIONS = [
        self::FUNCTION_GAUSSIAN,
        self::FUNCTION_EXPONENTIAL,
        self::FUNCTION_LINEAR,
    ];

    /**
     * Build a proximity boost.
     *
     * @param string|int|float $center the value to measure proximity from (number, date or location)
     * @param string $function use the `FUNCTION_` constants defined in this class
     * @param int|float|null $factor the boost strength, `null` to use the provider default
     * @throws InvalidArgumentException When the centre, function or factor are invalid
     */
    public function __construct(
        private readonly string|int|float $center,
        private readonly string $function,
        int|float|null $factor = null
    ) {
        parent::__construct($factor);

        $this->validateProximity();
    }

    /**
     * Get the value this boost measures proximity from.
     *
     * @return string|int|float - the centre, as it was given
     */
    public function getCenter(): string|int|float
    {
        return $this->center;
    }

    /**
     * Get the decay function of this boost.
     *
     * @return string - one of the `FUNCTION_` constants
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * Check that the centre is present and finite and that the function is supported.
     *
     * @throws InvalidArgumentException When the centre or function is invalid
     */
    private function validateProximity(): void
    {
        if (is_string($this->center) && trim($this->center) === '') {
            throw new InvalidArgumentException('A proximity boost requires a center');
        }

        if (is_float($this->center) && !is_finite($this->center)) {
            throw new InvalidArgumentException('A proximity boost center must be a finite number');
        }

        if (!in_array($this->function, self::FUNCTIONS, true)) {
            throw new InvalidArgumentException(sprintf('Invalid boost $function "%s"', $this->function));
        }
    }

}
