<?php

namespace Babble\Models\Fields;

use Babble\Models\Record;

class TextField extends Field
{
    public function validate(Record $record, $value)
    {
        return strlen($value) > 2; // TODO: Add proper validation.
    }
}