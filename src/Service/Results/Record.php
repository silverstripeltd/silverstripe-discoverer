<?php

namespace SilverStripe\Discoverer\Service\Results;

use Exception;
use JsonSerializable;
use SilverStripe\Control\Controller;
use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\Discoverer\Analytics\ClickController;
use SilverStripe\Model\ModelData;
use stdClass;

class Record extends ModelData implements JsonSerializable
{

    private ?AnalyticsData $analyticsData = null;

    private array $fields = [];

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

        if (ClickController::isActive()) {
            return $this->getClickLink($link);
        }

        $analyticsQueryParam = $analyticsData->forTemplate();

        return Controller::join_links($link, '?' . $analyticsQueryParam);
    }

    /**
     * The click route URL for a result link, which records the click and redirects to the link. Returns null when
     * there's no link or analytics data, or the click route isn't active (see ClickController).
     *
     * Templates can use this alongside the real link (for example in a data attribute) so that hovering over or copying
     * a result shows the real link, and a script swaps in this URL when the result is clicked.
     */
    public function getClickLink(?string $link = null): ?string
    {
        $analyticsData = $this->getAnalyticsData();

        if ($link === null || !$analyticsData || !ClickController::isActive()) {
            return null;
        }

        return ClickController::linkFor(Controller::join_links($link), $analyticsData);
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
     * @throws Exception
     */
    public function __set(string $property, mixed $value): void
    {
        if (!$value instanceof Field) {
            throw new Exception(sprintf('Field value must be an instance of %s', Field::class));
        }

        // Keep track of what fields have been added (atm, mostly just used for jsonSerialize, as we have no way of
        // retrieving dynamic data if we don't want what fields to look up)
        $this->fields[] = $property;

        parent::__set($property, $value);
    }

    public function jsonSerialize(): array|stdClass
    {
        $data = [
            'AnalyticsData' => $this->analyticsData?->jsonSerialize() ?? null,
        ];

        foreach ($this->fields as $field) {
            $data[$field] = $this->__get($field)->jsonSerialize();
        }

        if (!$data) {
            // Return an empty stdClass, so that json_encode provides an empty object (rather than an empty array)
            return new stdClass();
        }

        return $data;
    }

}
