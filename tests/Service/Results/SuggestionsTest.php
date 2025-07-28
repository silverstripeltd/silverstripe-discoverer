<?php

namespace SilverStripe\Discoverer\Tests\Service\Results;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Service\Results\Field;
use SilverStripe\Discoverer\Service\Results\Suggestions;

class SuggestionsTest extends SapphireTest
{

    public function testJsonSerialize(): void
    {
        $fieldOne = Field::create('raw1');
        $fieldTwo = Field::create('raw2');

        $suggestions = Suggestions::create(200);
        $suggestions->setTargetQueryUrl('place');
        $suggestions->setTargetQueryStringField('target');
        $suggestions->addSuggestion($fieldOne);
        $suggestions->addSuggestion($fieldTwo);

        $expected = [
            'targetQueryStringField' => 'target',
            'targetQueryUrl' => 'place',
            'suggestions' => [
                [
                    'raw' => 'raw1',
                    'formatted' => null,
                ],
                [
                    'raw' => 'raw2',
                    'formatted' => null,
                ],
            ],
        ];

        $this->assertEqualsCanonicalizing($expected, $suggestions->jsonSerialize());
    }

    public function testJsonSerializeEmpty(): void
    {
        $suggestions = Suggestions::create(200);

        $expected = [
            'targetQueryStringField' => null,
            'targetQueryUrl' => null,
            'suggestions' => [],
        ];

        $this->assertEqualsCanonicalizing($expected, $suggestions->jsonSerialize());
    }

}
