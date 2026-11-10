<?php

namespace SilverStripe\Discoverer\Tests\Query\Boost;

use InvalidArgumentException;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Query\Boost\ProximityBoost;
use TypeError;

class ProximityBoostTest extends SapphireTest
{

    public function testCreateViaInjectable(): void
    {
        $boost = ProximityBoost::create('now', ProximityBoost::FUNCTION_LINEAR, 4.5);

        $this->assertSame('now', $boost->getCenter());
        $this->assertEquals(ProximityBoost::FUNCTION_LINEAR, $boost->getFunction());
        $this->assertSame(4.5, $boost->getFactor());
    }

    public function testNumericCenterIsStoredAsGiven(): void
    {
        $this->assertSame(100, ProximityBoost::create(100, ProximityBoost::FUNCTION_GAUSSIAN)->getCenter());
    }

    public function testEachFunctionIsAccepted(): void
    {
        foreach (ProximityBoost::FUNCTIONS as $function) {
            $this->assertEquals($function, ProximityBoost::create('now', $function)->getFunction());
        }

        $this->assertCount(3, ProximityBoost::FUNCTIONS);
    }

    public function testNullCenterThrows(): void
    {
        // The signature rejects `null`, so it never reaches the blank-centre check
        $this->expectException(TypeError::class);

        ProximityBoost::create(null, ProximityBoost::FUNCTION_LINEAR);
    }

    public function testEmptyCenterThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ProximityBoost::create('', ProximityBoost::FUNCTION_LINEAR);
    }

    public function testWhitespaceCenterThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ProximityBoost::create('   ', ProximityBoost::FUNCTION_LINEAR);
    }

    public function testNonFiniteCenterIsRejected(): void
    {
        foreach ([NAN, INF] as $center) {
            try {
                ProximityBoost::create($center, ProximityBoost::FUNCTION_LINEAR);
                $this->fail('Expected InvalidArgumentException for center ' . $center);
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testUnknownFunctionThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ProximityBoost::create('now', 'quadratic');
    }

    public function testLowercaseFunctionThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ProximityBoost::create('now', 'linear');
    }

}
