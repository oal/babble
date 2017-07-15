<?php

namespace Babble;

use ArrayAccess;
use Yosymfony\Toml\Toml;

class Model implements ArrayAccess
{
    private $type;
    private $name;
    private $fields = [];

    private $id;
    private $data = [];

    public function __construct($model, $id)
    {
        $this->initModel($model);
        $this->initData($id);
    }

    public function __toString()
    {
        return json_encode($this->data);
    }


    /**
     * @param $modelType
     */
    private function initModel($modelType)
    {
        $this->type = $modelType;
        $modelFormat = Toml::Parse('../models/' . $modelType . '.toml');
        $this->name = $modelFormat['name'];
        $this->fields = $modelFormat['fields'];
    }

    /**
     * @param $id
     */
    private function initData($id)
    {
        $this->id = $id;

        $modelData = Toml::Parse('../content/' . $this->type . '/' . $id . '.toml');
        foreach ($this->fields as $key => $value) {
            $this->data[$key] = $modelData[$key];
        }
    }

    public function getType()
    {
        return $this->type;
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
}