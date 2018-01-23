<?php

namespace Babble\Models\Fields;

class HtmlField extends TextField
{
    function jsonSchema(): array
    {
        return [
            'type' => 'string'
        ];
    }
}