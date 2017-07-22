<?php

namespace Babble\Models\Fields;

use DateTime;

class DatetimeField extends Field
{
    public function validate($value)
    {
        return (DateTime::createFromFormat('Y-m-d H:i', $value) !== false);
    }
}