<?php

namespace Babble\Models\Fields;

use Babble\Models\Record;
use DateTime;

class DatetimeField extends Field
{
    public function validate(Record $record, $value)
    {
        return (DateTime::createFromFormat('Y-m-d H:i', $value) !== false);
    }
}