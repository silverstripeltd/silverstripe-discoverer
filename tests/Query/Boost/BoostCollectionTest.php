<?php

namespace SilverStripe\Discoverer\Tests\Query\Boost;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Query\Boost\Boost;
use SilverStripe\Discoverer\Query\Boost\BoostAdaptor;
use SilverStripe\Discoverer\Query\Boost\BoostCollection;
use SilverStripe\Discoverer\Query\Boost\NullBoostAdaptor;
use SilverStripe\Discoverer\Query\Boost\ProximityBoost;

class BoostCollectionTest extends SapphireTest
{

    public function testBoostsDefaultToEmpty(): void
    {
        $this->assertSame([], BoostCollection::create()->getBoosts());
    }

    public function testAddBoostReturnsSameInstance(): void
    {
        $collection = BoostCollection::create();

        $this->assertSame($collection, $collection->addBoost('featured', Boost::value(['yes'])));
    }

    public function testAddBoostKeepsInsertionOrderOnOneField(): void
    {
        $collection = BoostCollection::create();
        $first = Boost::value(['yes'], 2);
        $second = Boost::proximity('now', ProximityBoost::FUNCTION_LINEAR);

        $collection->addBoost('published_date', $first)->addBoost('published_date', $second);

        $this->assertSame(['published_date' => [$first, $second]], $collection->getBoosts());
    }

    /**
     * Proves an empty or whitespace-only field name is rejected.
     *
     * @param string $field - the blank field name
     */
    #[DataProvider('blankFieldProvider')]
    public function testAddBoostRejectsEmptyField(string $field): void
    {
        $this->expectException(InvalidArgumentException::class);

        BoostCollection::create()->addBoost($field, Boost::value(['yes']));
    }

    /**
     * Supplies field names that count as blank.
     *
     * @return array<string, array{0: string}> - the blank field names
     */
    public static function blankFieldProvider(): array
    {
        return [
            'empty' => [''],
            'space' => [' '],
            'tab and newline' => ["\t\n"],
        ];
    }

    public function testSetBoostsReplacesAllBoosts(): void
    {
        $collection = BoostCollection::create();
        $collection->addBoost('old', Boost::value(['yes']));

        $featured = Boost::value(['yes']);
        $date = Boost::proximity('now', ProximityBoost::FUNCTION_LINEAR);

        $this->assertSame($collection, $collection->setBoosts(['featured' => [$featured, $date]]));
        $this->assertSame(['featured' => [$featured, $date]], $collection->getBoosts());
    }

    public function testSetBoostsWithEmptyArrayClearsBoosts(): void
    {
        $collection = BoostCollection::create();
        $collection->addBoost('featured', Boost::value(['yes']));

        $collection->setBoosts([]);

        $this->assertSame([], $collection->getBoosts());
    }

    public function testSetBoostsAcceptsNumericFieldNames(): void
    {
        $boost = Boost::value(['yes']);
        // PHP turns the key into `int` 123, which must not trip the `string` type on `addBoost()`
        $collection = BoostCollection::create()->setBoosts(['123' => [$boost]]);

        $this->assertSame(['123' => [$boost]], $collection->getBoosts());
    }

    public function testSetBoostsRejectsNonArrayFieldEntry(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BoostCollection::create()->setBoosts(['featured' => Boost::value(['yes'])]);
    }

    public function testSetBoostsRejectsNonBoostElement(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BoostCollection::create()->setBoosts(['featured' => ['yes']]);
    }

    public function testSetBoostsRejectsEmptyFieldName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BoostCollection::create()->setBoosts(['' => [Boost::value(['yes'])]]);
    }

    public function testSetBoostsLeavesBoostsUnchangedWhenInvalid(): void
    {
        $existing = Boost::value(['yes']);
        $collection = BoostCollection::create()->addBoost('featured', $existing);

        try {
            $collection->setBoosts(['popularity' => [Boost::value(['high'])], 'category' => ['not a boost']]);
            $this->fail('Expected an InvalidArgumentException');
        } catch (InvalidArgumentException) {
            $this->assertSame(['featured' => [$existing]], $collection->getBoosts());
        }
    }

    /**
     * Proves `addBoost()` throws as soon as the adaptor rejects a boost, and does not store it.
     */
    public function testAddBoostThrowsWhenAdaptorRejectsBoost(): void
    {
        $collection = BoostCollection::create();
        $collection->setAdaptor($this->createRejectingAdaptor());

        try {
            $collection->addBoost('featured', Boost::value(['yes'], 6));
            $this->fail('Expected an InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Rejected boost on "featured"', $e->getMessage());
            $this->assertSame([], $collection->getBoosts());
        }
    }

    /**
     * Proves `setBoosts()` leaves the existing boosts in place when the adaptor rejects one of the replacements.
     */
    public function testSetBoostsLeavesBoostsUnchangedWhenAdaptorRejectsBoost(): void
    {
        $existing = Boost::value(['yes']);
        $collection = BoostCollection::create();
        $collection->setAdaptor($this->createRejectingAdaptor());
        $collection->addBoost('featured', $existing);

        try {
            $collection->setBoosts(['popularity' => [Boost::value(['high'], 2), Boost::value(['low'], 6)]]);
            $this->fail('Expected an InvalidArgumentException');
        } catch (InvalidArgumentException) {
            $this->assertSame(['featured' => [$existing]], $collection->getBoosts());
        }
    }

    public function testPrepareBoosts(): void
    {
        Injector::inst()->registerService(new MockBoostAdaptor(), BoostAdaptor::class);

        $collection = BoostCollection::create();
        $collection->addBoost('featured', Boost::value(['yes', 1], 2));
        $collection->addBoost('published_date', Boost::proximity('now', ProximityBoost::FUNCTION_LINEAR));

        $expected = [
            'featured' => [
                [
                    'factor' => 2.0,
                    'type' => 'value',
                    'values' => ['yes', 1],
                ],
            ],
            'published_date' => [
                [
                    'factor' => null,
                    'type' => 'proximity',
                    'center' => 'now',
                    'function' => ProximityBoost::FUNCTION_LINEAR,
                ],
            ],
        ];

        $this->assertSame($expected, $collection->getPreparedBoosts());
    }

    public function testNullBoostAdaptorIgnoresBoosts(): void
    {
        $collection = BoostCollection::create();
        $collection->setAdaptor(new NullBoostAdaptor());
        $collection->addBoost('featured', Boost::value(['yes']));

        $this->assertNull($collection->getPreparedBoosts());
    }

    /**
     * Proves a collection with no adaptor ignores its boosts rather than failing when they are prepared.
     */
    public function testNoAdaptorIgnoresBoosts(): void
    {
        $collection = new BoostCollection();
        $collection->addBoost('featured', Boost::value(['yes']));

        $this->assertNull($collection->getPreparedBoosts());
    }

    /**
     * Build an adaptor that rejects any boost with a factor above 5.
     *
     * @return BoostAdaptor - the rejecting adaptor
     */
    private function createRejectingAdaptor(): BoostAdaptor
    {
        return new class extends MockBoostAdaptor {

            /**
             * Reject any boost with a factor above 5.
             *
             * @param string $field - the name of the field being boosted
             * @param Boost $boost - the boost to check
             */
            public function validateBoost(string $field, Boost $boost): void
            {
                if ($boost->getFactor() > 5) {
                    throw new InvalidArgumentException(sprintf('Rejected boost on "%s"', $field));
                }
            }

        };
    }

}
