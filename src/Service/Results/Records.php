<?php

namespace SilverStripe\Discoverer\Service\Results;

use JsonSerializable;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\List\PaginatedList;

/**
 * @extends PaginatedList<ArrayList<Record>, Record>
 */
class Records extends PaginatedList implements JsonSerializable
{

    public function forTemplate(): string
    {
        return $this->renderWith(static::class);
    }

    public function jsonSerialize(): array
    {
        $records = [];

        foreach ($this->toArray() as $record) {
            $records[] = $record->jsonSerialize();
        }

        return $records;
    }

}
