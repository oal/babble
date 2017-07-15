<?php

namespace Babble\Content;

use Babble\ModelInstance;
use Exception;

interface ContentFilter
{
    public function isMatch(ModelInstance $model): bool;
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

    public function isMatch(ModelInstance $model): bool
    {
        $key = $model[$this->key];
        $value = $this->value;

        switch ($this->comparison) {
            case '=':
                return $key == $value;
            case '<':
                return $key < $value;
            case '>':
                return $key > $value;
            case '<=':
                return $key <= $value;
            case '>=':
                return $key >= $value;
        }

        throw new Exception('Invalid comparison operator "' . $this->comparison . '".');
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

    public function isMatch(ModelInstance $model)
    {
        $numFilters = count($this->filters);
        if ($numFilters === 0) return true;

        $matchedPrevious = $this->filters[0][1]->isMatch($model);
        if ($numFilters === 1) return $matchedPrevious;

        for ($i = 1; $i < $numFilters; $i++) {
            $filter = $this->filters[$i];
            $andOr = $filter[0];
            $filter = $filter[1];
            $matchedCurrent = $filter->isMatch($model);

            $previousAndOr = $this->filters[$i - 1][1];
            if (!$matchedCurrent && !$matchedPrevious && $andOr === 'AND' && $previousAndOr === 'AND') {
                return false;
            }

            if ($andOr === 'OR' && ($matchedCurrent || $matchedPrevious)) {
                $matchedCurrent = true;
            }

            $matchedPrevious = $matchedCurrent;
        }

        return $matchedPrevious;
    }
}
