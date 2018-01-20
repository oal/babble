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

    public function __construct(BaseModel $model, string $key, array $data)
    {
        $this->model = $model;
        $this->key = $key;
        $this->name = $data['name'];
        $this->type = $data['type'];
        $this->initOptions($data);
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

    function jsonSerialize()
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'type' => $this->type,
            'options' => $this->options
        ];
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
}