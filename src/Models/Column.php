<?php

namespace Babble\Models;

use Babble\Models\Fields\Field;
use JsonSerializable;

class Column implements JsonSerializable
{
    private $record;
    private $field;
    private $value;

    public function __construct(Record $record, Field $field, $value)
    {
        $this->record = $record;
        $this->field = $field;
        $this->value = $value;
    }

    function validate(): bool
    {
        return $this->field->validate($this->record, $this->value);
    }

    function jsonSerialize()
    {
        return $this->field->toJSON($this->value);
    }

    function getValue()
    {
        return $this->value;
    }

    function getView()
    {
        return $this->field->getView($this->value);
    }

    function setValue($value)
    {
        $this->value = $this->field->process($this->record, $value);
    }
}
