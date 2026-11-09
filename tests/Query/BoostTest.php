<?php

namespace SilverStripe\Discoverer\Tests\Query;

use InvalidArgumentException;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Query\Boost;

class BoostTest extends SapphireTest
{

    public function testValueBoost(): void
    {
        $boost = Boost::value(['featured', 3, 1.5], 2);

        $this->assertEquals(Boost::TYPE_VALUE, $boost->getType());
        $this->assertEquals(['featured', 3, 1.5], $boost->getValues());
        $this->assertNull($boost->getCenter());
        $this->assertNull($boost->getFunction());
        $this->assertSame(2.0, $boost->getFactor());
    }

    public function testProximityBoost(): void
    {
        $boost = Boost::proximity('now', Boost::FUNCTION_LINEAR, 4.5);

        $this->assertEquals(Boost::TYPE_PROXIMITY, $boost->getType());
        $this->assertEquals([], $boost->getValues());
        $this->assertSame('now', $boost->getCenter());
        $this->assertEquals(Boost::FUNCTION_LINEAR, $boost->getFunction());
        $this->assertSame(4.5, $boost->getFactor());
    }

    public function testNumericCenterIsStoredAsGiven(): void
    {
        $this->assertSame(100, Boost::proximity(100, Boost::FUNCTION_GAUSSIAN)->getCenter());
    }

    public function testNullFactorStaysNull(): void
    {
        $this->assertNull(Boost::value(['featured'])->getFactor());
        $this->assertNull(Boost::proximity('now', Boost::FUNCTION_GAUSSIAN)->getFactor());
    }

    public function testZeroFactorIsAllowed(): void
    {
        $this->assertSame(0.0, Boost::value(['featured'], 0)->getFactor());
    }

    public function testValuesAreReindexedToAList(): void
    {
        $this->assertSame(['a', 'b'], Boost::value([0 => 'a', 2 => 'b'])->getValues());
    }

    public function testNonFiniteFactorsAreRejected(): void
    {
        foreach ([NAN, INF] as $factor) {
            try {
                Boost::value(['featured'], $factor);
                $this->fail('Expected InvalidArgumentException for factor ' . $factor);
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testEachFunctionIsAccepted(): void
    {
        foreach (Boost::FUNCTIONS as $function) {
            $this->assertEquals($function, Boost::proximity('now', $function)->getFunction());
        }

        $this->assertCount(3, Boost::FUNCTIONS);
    }

    public function testCreateViaInjectable(): void
    {
        $boost = Boost::create(Boost::TYPE_VALUE, ['featured']);

        $this->assertEquals(['featured'], $boost->getValues());
    }

    public function testEmptyValuesThrow(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Boost::value([]);
    }

    public function testBoolValueThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Boost::value(['featured', true]);
    }

    public function testEmptyCenterThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Boost::proximity('', Boost::FUNCTION_LINEAR);
    }

    public function testUnknownFunctionThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Boost::proximity('now', 'quadratic');
    }

    public function testNegativeFactorThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Boost::value(['featured'], -1);
    }

    public function testUnknownTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Boost::create('functional');
    }

}
