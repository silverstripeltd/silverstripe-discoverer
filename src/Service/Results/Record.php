<?php

namespace SilverStripe\Discoverer\Service\Results;

use Exception;
use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\ViewableData;

class Record extends ViewableData
{

    private ?AnalyticsData $analyticsData = null;

    public function forTemplate(): DBHTMLText
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
            return $link;
        }

        $analyticsQueryParam = $analyticsData->forTemplate();

        // Trim any trailing "?" or "&", to rule out any links that have completely empty query params
        $link = rtrim($link, '&');
        $link = rtrim($link, '?');

        // If there are existing query params, then use '&' to concatenate, otherwise use '?'
        $concatenation = parse_url($link, PHP_URL_QUERY)
            ? '&'
            : '?';

        return sprintf('%s%s%s', $link, $concatenation, $analyticsQueryParam);
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
