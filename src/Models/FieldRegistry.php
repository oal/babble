<?php

namespace Babble\Models;


use Babble\Models\Fields;

class FieldRegistry
{
    private $fields = [];

    public function __construct()
    {
        $this->initBuiltin();
    }

    private function initBuiltin()
    {
        $this->register('boolean', Fields\BooleanField::class);
        $this->register('choice', Fields\ChoiceField::class);
        $this->register('datetime', Fields\DatetimeField::class);
        $this->register('file', Fields\FileField::class);
        $this->register('html', Fields\HtmlField::class);
        $this->register('image', Fields\ImageField::class);
        $this->register('list', Fields\ListField::class);
        $this->register('markdown', Fields\MarkdownField::class);
        $this->register('password', Fields\PasswordField::class);
        $this->register('tags', Fields\TagsField::class);
        $this->register('text', Fields\TextField::class);
    }

    public function register(string $name, $field)
    {
        $this->fields[$name] = $field;
    }

    public function get(string $name)
    {
        return $this->fields[$name];
    }
}