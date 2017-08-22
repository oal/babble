<?php

namespace Babble\Models\Fields;


use Babble\Models\Record;

class ChoiceField extends Field
{
    public function validate(Record $record, $data)
    {
        return true;
    }

    public function getView($data)
    {
        return new ChoiceView($this, $data);
    }
}

class ChoiceView
{
    private $field;
    private $value;

    public function __construct(ChoiceField $field, string $value)
    {
        $this->field = $field;
        $this->value = $value;
    }

    public function __toString()
    {
        return $this->value;
    }

    public function getDisplay()
    {
        $choices = $this->field->getOption('choices');
        if (array_key_exists($this->value, $choices)) return $choices[$this->value];

        return $this->value;
    }


}