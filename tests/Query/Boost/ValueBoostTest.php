<?php

namespace SilverStripe\Discoverer\Tests\Query\Boost;

use InvalidArgumentException;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Query\Boost\ValueBoost;

class ValueBoostTest extends SapphireTest
{

    public function testCreateViaInjectable(): void
    {
        $boost = ValueBoost::create(['featured'], 2);

        $this->assertEquals(['featured'], $boost->getValues());
        $this->assertSame(2.0, $boost->getFactor());
    }

    public function testValuesAreReindexedToAList(): void
    {
        $this->assertSame(['a', 'b'], ValueBoost::create([0 => 'a', 2 => 'b'])->getValues());
    }

    public function testEmptyValuesThrow(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ValueBoost::create([]);
    }

    public function testBoolValueThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ValueBoost::create(['featured', true]);
    }

    public function testNullValueThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ValueBoost::create(['featured', null]);
    }

    public function testNonFiniteFloatValuesAreRejected(): void
    {
        foreach ([NAN, INF, -INF] as $value) {
            try {
                ValueBoost::create(['featured', $value]);
                $this->fail('Expected InvalidArgumentException for value ' . $value);
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testEmptyAndWhitespaceStringValuesThrow(): void
    {
        foreach (['', '   ', "\t\n"] as $value) {
            try {
                ValueBoost::create(['featured', $value]);
                $this->fail('Expected InvalidArgumentException for a blank value');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

}
