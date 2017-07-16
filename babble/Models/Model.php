<?php

namespace Babble\Models;

use Babble\Exceptions\InvalidModelException;
use Yosymfony\Toml\Exception\ParseException;
use Yosymfony\Toml\Toml;

class Model
{
    private $type;
    private $name;
    private $fields = [];

    public function __construct(string $type)
    {
        try {
        $this->init($type);
        } catch (ParseException $e) {
            throw new InvalidModelException('Invalid model: ' . $type);
        }
    }

    private function init($modelType)
    {
        $this->type = $modelType;
        $modelFormat = Toml::Parse('../models/' . $modelType . '.toml');

        $this->name = $modelFormat['name'];

        $fields = $modelFormat['fields'];
        foreach ($fields as $key => $data) {
            $this->fields[] = new Field($key, $data);
        }
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    public function getFields()
    {
        return $this->fields;
    }
}

class Field
{
    private $key;
    private $label;
    private $type;

    public function __construct($key, $data)
    {
        $this->key = $key;
        $this->label = $data['label'];
        $this->type = $data['type'];
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

}