<?php

namespace SilverStripe\Discoverer\Query\Boost;

use InvalidArgumentException;

/**
 * A boost that raises documents whose field matches one of a list of values.
 *
 * Prefer `Boost::value()` to build one.
 */
class ValueBoost extends Boost
{

    /**
     * The field values this boost matches, as a list.
     *
     * @var list<string|int|float>
     */
    private readonly array $values;

    /**
     * Build a value boost.
     *
     * @param array<int, string|int|float> $values the field values to boost, as they were indexed
     * @param int|float|null $factor the boost strength, `null` to use the provider default
     * @throws InvalidArgumentException When the values or factor are invalid
     */
    public function __construct(array $values, int|float|null $factor = null)
    {
        parent::__construct($factor);

        // Filtered arrays keep their gaps, which would serialise as a JSON object rather than a list
        $this->values = array_values($values);

        $this->validateValues();
    }

    /**
     * Get the field values this boost matches.
     *
     * @return list<string|int|float> - the values, never empty
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Check that there is at least one value and that each is a usable scalar.
     *
     * @throws InvalidArgumentException When the list is empty or a value is invalid
     */
    private function validateValues(): void
    {
        if ($this->values === []) {
            throw new InvalidArgumentException('A value boost requires at least one value');
        }

        foreach ($this->values as $value) {
            // PHP casts `true` to '1', which never matches a stored "true"
            if (!is_string($value) && !is_int($value) && !is_float($value)) {
                throw new InvalidArgumentException(
                    sprintf('Boost values must be string, int or float, got %s', get_debug_type($value))
                );
            }

            if (is_float($value) && !is_finite($value)) {
                throw new InvalidArgumentException('Boost values must be finite numbers');
            }

            if (is_string($value) && trim($value) === '') {
                throw new InvalidArgumentException('Boost values must not be empty');
            }
        }
    }

}
