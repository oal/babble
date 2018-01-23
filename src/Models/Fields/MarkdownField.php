<?php

namespace Babble\Models\Fields;

use Michelf\MarkdownExtra;

class MarkdownField extends TextField
{
    public function getView($data)
    {
        if (!$data) return '';
        $parser = new MarkdownExtra();
        return $parser->transform($data);
    }

    function jsonSchema(): array
    {
        return [
            'type' => 'string'
        ];
    }
}