<?php

namespace Babble\Models\Fields;

use Babble\Models\Record;

class TagsField extends Field
{
    public function validate(Record $record, $value)
    {
        return true; // TODO: Add proper validation.
    }
}