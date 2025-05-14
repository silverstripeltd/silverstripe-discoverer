<?php

namespace SilverStripe\Discoverer\Service\Results;

use Exception;
use SilverStripe\Control\Controller;
use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\Model\ModelData;

class Record extends ModelData
{

    private ?AnalyticsData $analyticsData = null;

    public function forTemplate(): string
    {
        return $this->renderWith(static::class);
    }

    public function getDecoratedLink(?string $link = null): ?string
    {
        // Explicit null check, because an empty link ('') is valid
        if ($link === null) {
            return null;
        }

        $analyticsData = $this->getAnalyticsData();

        if (!$analyticsData) {
            // All links should be standardised, even if they don't contain analytics data
            return Controller::join_links($link);
        }

        $analyticsQueryParam = $analyticsData->forTemplate();

        return Controller::join_links($link, '?' . $analyticsQueryParam);
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
