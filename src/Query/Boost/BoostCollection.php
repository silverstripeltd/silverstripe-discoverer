<?php

namespace SilverStripe\Discoverer\Query\Boost;

use InvalidArgumentException;
use SilverStripe\Core\Injector\Injectable;

/**
 * The query-time boosts on a `Query`, keyed by field name.
 *
 * It validates field names and hands the boosts to the registered `BoostAdaptor` for translation into the provider's
 * request format.
 */
class BoostCollection
{

    use Injectable;

    /**
     * The adaptor that translates these boosts for the search provider.
     */
    private ?BoostAdaptor $adaptor = null;

    /**
     * The boosts keyed by field name, each holding a list in insertion order.
     *
     * @var array<string, Boost[]>
     */
    private array $boosts = [];

    /**
     * The services the Injector sets on this collection.
     */
    private static array $dependencies = [
        'adaptor' => '%$' . BoostAdaptor::class,
    ];

    /**
     * Set the adaptor that translates these boosts.
     *
     * @param BoostAdaptor|null $adaptor the provider's boost adaptor
     */
    public function setAdaptor(?BoostAdaptor $adaptor): void
    {
        $this->adaptor = $adaptor;
    }

    /**
     * Add a boost for a field. Several boosts on one field are kept in the order they were added.
     *
     * @param string $field the name of the field to boost
     * @param Boost $boost the boost to apply to the field
     * @return static - this collection, for chaining
     * @throws InvalidArgumentException When the field name is empty, or the adaptor rejects the boost
     */
    public function addBoost(string $field, Boost $boost): static
    {
        if ($field === '') {
            throw new InvalidArgumentException('Boost $field must not be empty');
        }

        $this->adaptor?->validateBoost($field, $boost);

        $this->boosts[$field][] = $boost;

        return $this;
    }

    /**
     * Replace all boosts, or none of them when any entry is invalid. Pass an empty array to clear them.
     *
     * @param array<string, Boost[]> $boosts the boosts keyed by field name, in the shape `getBoosts()` returns
     * @return static - this collection, for chaining
     * @throws InvalidArgumentException When a field name is empty, a field's entry is not an array, an element is
     *     not a `Boost`, or the adaptor rejects a boost
     */
    public function setBoosts(array $boosts): static
    {
        $previous = $this->boosts;
        $this->boosts = [];

        try {
            foreach ($boosts as $field => $fieldBoosts) {
                if (!is_array($fieldBoosts)) {
                    throw new InvalidArgumentException('Boosts for each field must be an array of Boost instances');
                }

                foreach ($fieldBoosts as $boost) {
                    if (!$boost instanceof Boost) {
                        throw new InvalidArgumentException(
                            sprintf('Boosts must be instances of Boost, got %s', get_debug_type($boost))
                        );
                    }

                    // Numeric field names arrive as `int` keys; cast so this still type-checks under `strict_types`
                    $this->addBoost((string) $field, $boost);
                }
            }
        } catch (InvalidArgumentException $e) {
            // Leave the collection unchanged when any entry is invalid
            $this->boosts = $previous;

            throw $e;
        }

        return $this;
    }

    /**
     * Get all boosts.
     *
     * @return array<int|string, Boost[]> - the boosts keyed by field name, each holding a list in insertion order;
     *     numeric field names come back as `int` keys, so adaptors must cast them to `string`
     */
    public function getBoosts(): array
    {
        return $this->boosts;
    }

    /**
     * Get the boosts translated by the registered adaptor.
     *
     * @return mixed - the boosts in the provider's format, or `null` when the provider ignores boosts
     */
    public function getPreparedBoosts(): mixed
    {
        return $this->adaptor->prepareBoosts($this);
    }

}
