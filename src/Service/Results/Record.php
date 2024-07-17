<?php

namespace SilverStripe\Discoverer\Service\Results;

use Exception;
use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\ViewableData;

class Record extends ViewableData
{

    /**
     * This is the one field that we attempt to make predictable
     */
    private ?AnalyticsData $analyticsData = null;

    public function forTemplate(): DBHTMLText
    {
        return $this->renderWith(static::class);
    }

    public function getAnalyticsData(): ?AnalyticsData
    {
        return $this->analyticsData;
    }

    public function setAnalyticsData(?AnalyticsData $analyticsData): Record
    {
        $this->analyticsData = $analyticsData;

        return $this;
    }

    /**
     * @param string $property
     * @param mixed $value
     * @throws Exception
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function __set($property, $value): void
    {
        if (!$value instanceof Field) {
            throw new Exception(sprintf('Field value must be an instance of %s', Field::class));
        }

        parent::__set($property, $value);
    }

}
