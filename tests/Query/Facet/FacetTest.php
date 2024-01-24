<?php

namespace SilverStripe\Discoverer\Tests\Query\Facet;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Query\Facet\Facet;
use SilverStripe\Discoverer\Query\Facet\FacetRange;

class FacetTest extends SapphireTest
{

    public function testLimit(): void
    {
        $facet = Facet::create();

        $this->assertNull($facet->getLimit());

        $facet->setLimit(10);

        $this->assertEquals(10, $facet->getLimit());
    }

    public function testName(): void
    {
        $facet = Facet::create();

        $this->assertNull($facet->getName());

        $facet->setName('facetName');

        $this->assertEquals('facetName', $facet->getName());
    }

    public function testProperty(): void
    {
        $facet = Facet::create();

        $this->assertNull($facet->getProperty());

        $facet->setProperty('fieldName');

        $this->assertEquals('fieldName', $facet->getProperty());
    }

    /**
     * @dataProvider provideTypes
     */
    public function testType(string $type): void
    {
        $facet = Facet::create();

        $facet->setType($type);

        $this->assertEquals($type, $facet->getType());
    }

    public function provideTypes(): array
    {
        $tests = [];

        foreach (Facet::TYPES as $type) {
            $tests[] = [$type];
        }

        return $tests;
    }

    public function testInvalidType(): void
    {
        $this->expectExceptionMessage('Invalid type "INVALID" provided');

        $facet = Facet::create();

        // Should throw our Exception
        $facet->setType('INVALID');
    }

    public function testRanges(): void
    {
        $facet = Facet::create();

        $facet->addRange(1, 2, 'firstName');
        $facet->addRange(10);
        $facet->addRange(to: 20);
        $facet->addRange(name: 'secondName');

        $ranges = $facet->getRanges();

        $this->assertCount(4, $ranges);

        /** @var FacetRange $rangeOne */
        $rangeOne = array_shift($ranges);
        /** @var FacetRange $rangeTwo */
        $rangeTwo = array_shift($ranges);
        /** @var FacetRange $rangeThree */
        $rangeThree = array_shift($ranges);
        /** @var FacetRange $rangeFour */
        $rangeFour = array_shift($ranges);

        $this->assertEquals(1, $rangeOne->getFrom());
        $this->assertEquals(2, $rangeOne->getTo());
        $this->assertEquals('firstName', $rangeOne->getName());

        $this->assertEquals(10, $rangeTwo->getFrom());
        $this->assertNull($rangeTwo->getTo());
        $this->assertNull($rangeTwo->getName());

        $this->assertNull($rangeThree->getFrom());
        $this->assertEquals(20, $rangeThree->getTo());
        $this->assertNull($rangeThree->getName());

        $this->assertNull($rangeFour->getFrom());
        $this->assertNull($rangeFour->getTo());
        $this->assertEquals('secondName', $rangeFour->getName());
    }

}
