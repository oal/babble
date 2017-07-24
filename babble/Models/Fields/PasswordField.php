<?php

namespace Babble\Models\Fields;

class PasswordField extends Field
{
    public function validate($value)
    {
        return strlen($value) > 2; // TODO: Add proper validation.
    }

    public function process(string $recordId, $data)
    {
        return  password_hash($data, PASSWORD_DEFAULT);
    }
}