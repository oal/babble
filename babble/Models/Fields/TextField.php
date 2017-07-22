<?php

namespace Babble\Models\Fields;

class TextField extends Field
{
    public function validate($value)
    {
        return count($value) > 2; // TODO: Add proper validation.
    }
}