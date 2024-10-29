<?php

namespace SilverStripe\Discoverer\Service\Results;

use ArrayIterator;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\View\ViewableData;
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
class Suggestions extends ViewableData
{

    private string $targetQueryStringField = '';

    private string $targetQueryUrl = '';

    private bool $success = false;

    private array $suggestions = [];

    public function forTemplate(): DBHTMLText
    {
        return $this->renderWith(static::class);
    }

    public function getTargetQueryStringField(): string
    {
        return $this->targetQueryStringField;
    }

    public function setTargetQueryStringField(string $targetQueryStringField): self
    {
        $this->targetQueryStringField = $targetQueryStringField;

        return $this;
    }

    public function getTargetQueryUrl(): string
    {
        return $this->targetQueryUrl;
    }

    public function setTargetQueryUrl(string $targetQueryUrl): self
    {
        $this->targetQueryUrl = $targetQueryUrl;

        return $this;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): self
    {
        $this->success = $success;

        return $this;
    }

    public function addSuggestion(string $suggestions): self
    {
        $this->suggestions[] = $suggestions;

        return $this;
    }

    public function getSuggestions(): array
    {
        return $this->suggestions;
    }

    public function setSuggestions(array $suggestions): self
    {
        // Specifically using a loop and addSuggestion() to make sure that all items are of type string
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

        return new ArrayIterator($this->convertArrayForTemplate());
    }

    /**
     * Silverstripe 5.3 will have native support for looping primitives in templates:
     * https://github.com/silverstripe/silverstripe-framework/issues/11196
     *
     * We need to support versions of Silverstripe below 5.3 though, so we need this polyfill. It emulates the
     * template implementation method from Silverstripe 5.3, so we should be able to remove this later without
     * negatively impacting any project's template implementation
     */
    private function convertArrayForTemplate(): array
    {
        $arrayList = [];

        foreach ($this->suggestions as $suggestion) {
            $text = DBText::create('suggestion');
            $text->setValue($suggestion);

            $arrayList[] = $text;
        }

        return $arrayList;
    }

}
