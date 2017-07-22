<?php

namespace Babble\Models\Fields;

class BooleanField extends Field
{
    public function validate($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN); // TODO: Add proper validation.
    }
}