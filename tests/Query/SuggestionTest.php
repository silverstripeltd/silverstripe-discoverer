<?php

namespace SilverStripe\Discoverer\Tests\Query;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Query\Suggestion;

class SuggestionTest extends SapphireTest
{

    public function testQueryString(): void
    {
        $suggestion = Suggestion::create('initial query string');

        $this->assertEquals('initial query string', $suggestion->getQueryString());

        $suggestion->setQueryString('modified query string');

        $this->assertEquals('modified query string', $suggestion->getQueryString());
    }

    public function testSetLimit(): void
    {
        $suggestion = Suggestion::create('initial query string', 10);

        $this->assertEquals(10, $suggestion->getLimit());

        $suggestion->setLimit(20);

        $this->assertEquals(20, $suggestion->getLimit());
    }

    public function testAddSetFields(): void
    {
        $suggestion = Suggestion::create('initial query string', null, ['test1']);

        $this->assertEquals(['test1'], $suggestion->getFields());

        $suggestion->addField('test2');

        $this->assertEquals(['test1', 'test2'], $suggestion->getFields());

        $suggestion->setFields(['test3']);

        $this->assertEquals(['test3'], $suggestion->getFields());
    }

}
