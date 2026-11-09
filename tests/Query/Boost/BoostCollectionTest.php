<?php

namespace SilverStripe\Discoverer\Tests\Query\Boost;

use InvalidArgumentException;
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

    public function testAddBoostRejectsEmptyField(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BoostCollection::create()->addBoost('', Boost::value(['yes']));
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

}
