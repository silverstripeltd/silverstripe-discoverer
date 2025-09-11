<?php

namespace SilverStripe\Discoverer\Service\Results;

use ArrayIterator;
use Traversable;

/**
 * $targetQueryStringField and $targetQueryUrl fields:
 *
 * Search suggestions are often used as links back to a search form. In order to do that, you need to know where the
 * form is localed (a page URL in many cases) and what field is used for holding the search query string. These fields
 * are not automatically populated, but you can populate them if you would like to keep that information localised to
 * the Suggestions object, so that it might be easier for you to access them in your template (or wherever you need
 * them)
 *
 * See also: Suggestions.ss for an example of how these fields are implemented to create links back to a search form
 */
class Suggestions extends Response
{

    private string $targetQueryStringField = '';

    private string $targetQueryUrl = '';

    /**
     * @var Field[]
     */
    private array $suggestions = [];

    public function getTargetQueryStringField(): string
    {
        return $this->targetQueryStringField;
    }

    public function setTargetQueryStringField(string $targetQueryStringField): static
    {
        $this->targetQueryStringField = $targetQueryStringField;

        return $this;
    }

    public function getTargetQueryUrl(): string
    {
        return $this->targetQueryUrl;
    }

    public function setTargetQueryUrl(string $targetQueryUrl): static
    {
        $this->targetQueryUrl = $targetQueryUrl;

        return $this;
    }

    public function addSuggestion(Field $suggestion): static
    {
        $this->suggestions[] = $suggestion;

        return $this;
    }

    public function getSuggestions(): array
    {
        return $this->suggestions;
    }

    public function setSuggestions(array $suggestions): static
    {
        // Specifically using a loop and addSuggestion() to make sure that all items are of type Field
        foreach ($suggestions as $suggestion) {
            $this->addSuggestion($suggestion);
        }

        return $this;
    }

    public function getIterator(): Traversable
    {
        if (!$this->suggestions) {
            return new ArrayIterator();
        }

        return new ArrayIterator($this->getSuggestions());
    }

    public function jsonSerialize(): array
    {
        $suggestions = [];

        foreach ($this->suggestions as $suggestion) {
            $suggestions[] = $suggestion->jsonSerialize();
        }

        return [
            'targetQueryStringField' => $this->getTargetQueryStringField(),
            'targetQueryUrl' => $this->getTargetQueryUrl(),
            'suggestions' => $suggestions,
        ];
    }

}
