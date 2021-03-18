<?php


namespace Babble\Models;


use Babble\Models\Fields\Field;

class TemplateField
{
    /**
     * @var Field
     */
    private $field;

    public function __construct(Field $field)
    {
        $this->field = $field;
    }

    public function name()
    {
        return $this->field->getName();
    }

    public function key(): string
    {
        return $this->field->getKey();
    }

    public function type()
    {
        return $this->field->getType();
    }

    public function option(string $key)
    {
        return $this->field->getOption($key);
    }

    public function __toString()
    {
        return json_encode($this->field->jsonSerialize(), JSON_PRETTY_PRINT);
    }


}