<?php

namespace SilverStripe\Discoverer\Tests\Query\Boost;

use InvalidArgumentException;
use ReflectionClass;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Query\Boost\Boost;
use SilverStripe\Discoverer\Query\Boost\ProximityBoost;
use SilverStripe\Discoverer\Query\Boost\ValueBoost;

class BoostTest extends SapphireTest
{

    public function testBoostIsAbstract(): void
    {
        $this->assertTrue((new ReflectionClass(Boost::class))->isAbstract());
    }

    public function testValueReturnsValueBoost(): void
    {
        $boost = Boost::value(['featured', 3, 1.5], 2);

        $this->assertInstanceOf(ValueBoost::class, $boost);
        $this->assertEquals(['featured', 3, 1.5], $boost->getValues());
        $this->assertSame(2.0, $boost->getFactor());
    }

    public function testProximityReturnsProximityBoost(): void
    {
        $boost = Boost::proximity('now', ProximityBoost::FUNCTION_LINEAR, 4.5);

        $this->assertInstanceOf(ProximityBoost::class, $boost);
        $this->assertSame('now', $boost->getCenter());
        $this->assertEquals(ProximityBoost::FUNCTION_LINEAR, $boost->getFunction());
        $this->assertSame(4.5, $boost->getFactor());
    }

    public function testNamedConstructorsUseTheInjector(): void
    {
        Injector::inst()->load([ValueBoost::class => ['class' => ValueBoostReplacement::class]]);
        Injector::inst()->load([ProximityBoost::class => ['class' => ProximityBoostReplacement::class]]);

        $this->assertInstanceOf(ValueBoostReplacement::class, Boost::value(['featured']));
        $this->assertInstanceOf(
            ProximityBoostReplacement::class,
            Boost::proximity('now', ProximityBoost::FUNCTION_LINEAR)
        );
    }

    public function testNullFactorStaysNull(): void
    {
        $this->assertNull(Boost::value(['featured'])->getFactor());
        $this->assertNull(Boost::proximity('now', ProximityBoost::FUNCTION_GAUSSIAN)->getFactor());
    }

    public function testZeroFactorIsAllowed(): void
    {
        $this->assertSame(0.0, Boost::value(['featured'], 0)->getFactor());
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

    public function testNegativeFactorThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Boost::value(['featured'], -1);
    }

    public function testProximityFactorIsValidated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Boost::proximity('now', ProximityBoost::FUNCTION_LINEAR, -1);
    }

}
