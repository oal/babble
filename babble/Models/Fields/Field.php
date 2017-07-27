<?php

namespace Babble\Models\Fields;

use Babble\Models\Model;
use Babble\Models\Record;
use JsonSerializable;

class Field implements JsonSerializable
{
    private $model;
    private $key;
    private $name;
    private $type;
    private $options = [];

    public function __construct(Model $model, string $key, array $data)
    {
        $this->model = $model;
        $this->key = $key;
        $this->name = $data['name'];
        $this->type = $data['type'];
        if (array_key_exists('options', $data)) $this->options = $data['options'];
    }

    /**
     * @return mixed
     */
    public function getKey()
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
        return $this->options[$key];
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function getView($data)
    {
        return $data;
    }

    public function toJSON($value) {
        return $value;
    }
}