<?php

namespace Babble\Models\Fields;


use Babble\Models\Model;
use Babble\Models\Record;
use Babble\Models\TemplateRecord;

class ChoiceField extends Field
{
    public function validate(Record $record, $data)
    {
        return true;
    }

    public function getView($data)
    {
        if (!isset($data)) return null;

        $modelType = $this->getOption('model');
        if ($modelType) {
            $record = Record::fromDisk(new Model($modelType), $data);
            return new TemplateRecord($record);
        } else {
            return new ChoiceView($this, $data);
        }
    }
}

class ChoiceView
{
    private $field;
    private $value;

    public function __construct(ChoiceField $field, $value)
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