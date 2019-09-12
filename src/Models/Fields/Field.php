<?php

namespace Babble\Models\Fields;

use Babble\Models\BaseModel;
use Babble\Models\Record;
use JsonSerializable;

class Field implements JsonSerializable
{
    private $model;
    private $key;
    private $name;
    private $type;
    private $options = [];

    private $isRequired = true;
    private $validation = [];

    public function __construct(BaseModel $model, string $key, array $data)
    {
        $this->model = $model;
        $this->key = $key;
        $this->name = $data['name'];
        $this->type = $data['type'];
        $this->isRequired = boolval($data['required'] ?? true);
        $this->initOptions($data);
        $this->initValidation($data);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function validate(Record $record, $data)
    {
        return true;
    }

    public function process(Record $record, $data)
    {
        return $data;
    }

    function jsonSchema(): array
    {
        return $this->validation;
    }

    function jsonSerialize()
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'type' => $this->type,
            'options' => $this->options
        ];
    }

    public function __toString()
    {
        return json_encode($this);
    }


    public function getOption($key)
    {
        return $this->options[$key] ?? null;
    }

    public function getModel(): BaseModel
    {
        return $this->model;
    }

    public function getView($data)
    {
        return $data;
    }

    public function toJSON($value)
    {
        return $value;
    }

    protected function initOptions(array $data)
    {
        $this->options = $data['options'] ?? [];
    }

    private function initValidation($data)
    {
        $this->validation = $data['validation'] ?? [];
    }

    // Generic comparisons
    public function isEqual($a, $b)
    {
        return $a == $b;
    }

    public function isNotEqual($a, $b)
    {
        return $a != $b;
    }

    public function isLess($a, $b)
    {
        return $a < $b;
    }

    public function isGreater($a, $b)
    {
        return $a > $b;
    }

    public function isLessOrEqual($a, $b)
    {
        return $a <= $b;
    }

    public function isGreaterOrEqual($a, $b)
    {
        return $a >= $b;
    }

    /**
     * @param $data
     * @param $value
     * @throws \Exception
     */
    public function contains($data, $value)
    {
        throw new \Exception('Contains is not implemented for this field type.');
    }

    /**
     * @return bool|mixed
     */
    public function isRequired()
    {
        return $this->isRequired;
    }
}