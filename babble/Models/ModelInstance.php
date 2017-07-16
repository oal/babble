<?php

namespace Babble;

use ArrayAccess;
use Babble\Models\Model;
use JsonSerializable;
use Yosymfony\Toml\Toml;

class ModelInstance implements ArrayAccess, JsonSerializable
{
    private $model;

    private $id;
    private $data = [];

    public function __construct(Model $model, $id)
    {
        $this->model = $model;
        $this->initData($id);
    }

    public function __toString()
    {
        return json_encode($this->data);
    }

    /**
     * @param $id
     */
    private function initData($id)
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

    public function offsetSet($offset, $value)
    {
    }

    public function offsetUnset($offset)
    {
    }

    function jsonSerialize()
    {
        return array_merge(['id' => $this->id], $this->data);
    }
}