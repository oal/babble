<?php

namespace Babble\Content;

use Babble\Models\Record;
use Exception;

interface ContentFilter
{
    public function isMatch(Record $record): bool;
}

class WhereFilter implements ContentFilter
{
    private $key;
    private $comparison;
    private $value;

    public function __construct($key, $comparison, $value)
    {
        $this->key = $key;
        $this->comparison = $comparison;
        $this->value = $value;
    }

    public function isMatch(Record $record): bool
    {
        $key = $record->getValue($this->key);
        $field = $record->getModel()->getField($this->key);

        switch ($this->comparison) {
            case '=':
                return $field->isEqual($key, $this->value);
            case '!=':
                return $field->isNotEqual($key, $this->value);
            case '<':
                return $field->isLess($key, $this->value);
            case '>':
                return $field->isGreater($key, $this->value);
            case '<=':
                return $field->isLessOrEqual($key, $this->value);
            case '>=':
                return $field->isGreater($key, $this->value);
        }

        throw new Exception('Invalid comparison operator "' . $this->comparison . '".');
    }
}

class WhereContainsFilter implements ContentFilter
{
    private $key;
    private $value;

    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function isMatch(Record $record): bool
    {
        $storedValue = $record->getValue($this->key);
        $field = $record->getModel()->getField($this->key);

        return $field->contains($storedValue, $this->value);
    }
}

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
