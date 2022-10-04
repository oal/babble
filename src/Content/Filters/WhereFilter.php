<?php

namespace Babble\Content\Filters;

use Babble\Models\Record;

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
                return $field->isGreaterOrEqual($key, $this->value);
        }

        throw new \Exception('Invalid comparison operator "' . $this->comparison . '".');
    }
}
