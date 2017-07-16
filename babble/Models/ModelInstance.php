<?php

namespace Babble;

use ArrayAccess;
use Babble\Models\Model;
use InvalidModelFieldException;
use JsonSerializable;
use Yosymfony\Toml\Toml;

class ModelInstance implements ArrayAccess, JsonSerializable
{
    private $model;

    private $id;
    private $data = [];

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function __toString()
    {
        return json_encode($this->data);
    }

    /**
     * @param $id
     */
    private function loadFromDisk($id)
    {
        $this->id = $id;

        $modelData = Toml::Parse('../content/' . $this->getType() . '/' . $id . '.toml');
        foreach ($this->model->getFields() as $field) {
            $this->data[$field->getKey()] = $modelData[$field->getKey()];
        }
    }

    public function getType()
    {
        return $this->model->getType();
    }

    public function offsetExists($offset)
    {
        if ($offset === 'id') return true;
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        if ($offset === 'id') return $this->id;
        return $this->data[$offset];
    }

    public function offsetSet($key, $value)
    {
        if (!$this->model->hasField($key)) {
            $modelName = $this->model->getName();
            throw new InvalidModelFieldException("Field \"$key\" does not exist on model \"$modelName\"");
        }
        $this->data[$key] = $value;
    }

    public function offsetUnset($offset)
    {
    }

    function jsonSerialize()
    {
        return array_merge(['id' => $this->id], $this->data);
    }

    static function fromDisk(Model $model, string $id)
    {
        $modelInstance = new ModelInstance($model);
        $modelInstance->loadFromDisk($id);

        return $modelInstance;
    }

    static function fromData(Model $model, array $data)
    {
        $modelInstance = new ModelInstance($model);
        foreach ($model->getFields() as $field) {
            $key = $field->getKey();
            $value = $data[$key];
            if (!empty($value)) $modelInstance[$key] = $value;
        }

        return $modelInstance;
    }
}