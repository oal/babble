<?php

namespace Babble\Models;

use Babble\Exceptions\InvalidModelException;
use JsonSerializable;
use Symfony\Component\Finder\Finder;
use Yosymfony\Toml\Exception\ParseException;
use Yosymfony\Toml\Toml;

class Model implements JsonSerializable
{
    private $type;

    private $name;
    private $namePlural;

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

        $this->initName($modelFormat);
        $this->initFields($modelFormat['fields']);
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

    function jsonSerialize()
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'name_plural' => $this->namePlural,
            'fields' => $this->fields
        ];
    }

    static function all()
    {
        $finder = new Finder();
        $files = $finder->files()->in('../models');

        $models = [];
        foreach ($files as $file) {
            $modelName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $models[] = new Model($modelName);
        }

        return $models;
    }

    /**
     * @param $modelFormat
     * @return mixed
     */
    private function initName($modelFormat)
    {
        $this->name = $modelFormat['name'];
        if (!empty($modelFormat['name_plural'])) {
            $this->namePlural = $modelFormat['name_plural'];
        } else if (substr($this->name, count($this->name) - 1) === 's') {
            $this->namePlural = $this->name;
        } else {
            $this->namePlural = $this->name . 's';
        }
    }

    private function initFields($fields)
    {
        foreach ($fields as $key => $data) {
            $this->fields[] = new Field($key, $data);
        }
    }
}

class Field implements JsonSerializable
{
    private $key;
    private $label;
    private $type;
    private $options = [];

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

    function jsonSerialize()
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'options' => $this->options
        ];
    }
}