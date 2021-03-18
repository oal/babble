<?php

namespace Babble\Content\Filters;

use Babble\Models\Record;

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