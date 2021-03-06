<?php

namespace Babble\Content\Filters;

use Babble\Models\Record;

class FilterContainer
{
    private $filters = [];

    public function and (ContentFilter $filter)
    {
        $this->filters[] = ['AND', $filter];
        return $this;
    }

    public function or (ContentFilter $filter)
    {
        $this->filters[] = ['OR', $filter];
        return $this;
    }

    public function isMatch(Record $record): bool
    {
        $numFilters = count($this->filters);
        if ($numFilters === 0) return true;

        $matchedPrevious = $this->filters[0][1]->isMatch($record);
        if ($numFilters === 1) return $matchedPrevious;

        for ($i = 1; $i < $numFilters; $i++) {
            $filter = $this->filters[$i];
            $andOr = $filter[0];
            $filter = $filter[1];
            $matchedCurrent = $filter->isMatch($record);

            if ($andOr === 'AND') $matchedCurrent = $matchedCurrent & $matchedPrevious;
            if ($andOr === 'OR') $matchedCurrent = $matchedCurrent | $matchedPrevious;

            // TODO: Return early if result can't be true anymore?

            $matchedPrevious = $matchedCurrent;
        }

        return $matchedPrevious;
    }
}
