<?php

namespace Babble\Models\Fields;

use Babble\Models\Record;

class PasswordField extends Field
{
    public function validate(Record $record, $value)
    {
        if ($value && strlen($value) > 2) return true;
        return !!$record->getValue($this->getKey());
    }

    public function process(Record $record, $data)
    {
        // If password provided, hash and return hash. If null, return original value (password unchanged).
        if ($data !== null) return password_hash($data, PASSWORD_DEFAULT);
        return $record->getValue($this->getKey());
    }

    public function toJSON($value)
    {
        // Never expose hash through the API / in JSON.
        return null;
    }
}