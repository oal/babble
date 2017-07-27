<?php

namespace Babble\Models;

use ArrayAccess;
use JsonSerializable;

class ArrayAccessRecord implements ArrayAccess, JsonSerializable
{
    private $record;

    public function __construct(Record $record)
    {
        $this->record = $record;
    }

    public function getType()
    {
        return $this->record->getType();
    }


    public function offsetExists($offset)
    {
        return !!$this->record->getView($offset);
    }

    public function offsetGet($offset)
    {
        return $this->record->getView($offset);
    }

    public function offsetSet($offset, $value)
    {
    }

    public function offsetUnset($offset)
    {
    }

    function jsonSerialize()
    {
        return $this->record;
    }
}
