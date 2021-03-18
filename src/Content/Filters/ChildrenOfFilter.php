<?php

namespace Babble\Content\Filters;

use Babble\Models\Record;

class ChildrenOfFilter implements ContentFilter
{
    private $parentId;

    public function __construct($parentId)
    {
        if (!is_string($parentId)) {
            $parentId = $parentId['id'];
        }
        $this->parentId = $parentId;
    }

    public function isMatch(Record $record): bool
    {
        $storedValue = $record->getValue('id');
        $idWithSlash = substr($storedValue, 0, strlen($this->parentId) + 1);
        $parentIdWithSlash = $this->parentId . '/';
        return $idWithSlash === $parentIdWithSlash;
    }
}

