<?php

namespace Babble\Models\Fields;

use Babble\Models\Record;

class BooleanField extends Field
{
    public function validate(Record $record, $value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) || $value === null;
    }

    public function process(Record $record, $data)
    {
        return $data === true;
    }

    function jsonSchema(): array
    {
        return [
            'type' => 'boolean'
        ];
    }
}