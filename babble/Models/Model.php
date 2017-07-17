<?php

namespace Babble\Models;

use Babble\Exceptions\InvalidModelException;
use JsonSerializable;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Yosymfony\Toml\Exception\ParseException;
use Yosymfony\Toml\Toml;

class Model implements JsonSerializable
{
    private $type;

    private $name;
    private $namePlural;

    private $options = [];

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
        $this->initOptions($modelFormat);
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
            'options' => $this->options,
            'fields' => $this->fields
        ];
    }

    public function exists(string $id)
    {
        $fs = new Filesystem();
        return $fs->exists('../content/' . $this->getType() . '/' . $id . '.toml');
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

    /**
     * @param $modelFormat
     */
    private function initOptions($modelFormat)
    {
        if (empty($modelFormat['options'])) return;

        $options = $modelFormat['options'];
        if (is_array($options)) $this->options = $options;
    }

    public function validate(array $data)
    {
        foreach ($this->getFields() as $field) {
            $value = $data[$field->getKey()];
            if (!$field->validate($value)) return false;
        }
        return true;
    }

    public function hasField($key)
    {
        // TODO: Cache field names / keys somewhere to avoid this loop?
        foreach ($this->fields as $field) {
            if ($field->getKey() === $key) return true;
        }
        return false;
    }
}

class Field implements JsonSerializable
{
    private $key;
    private $name;
    private $type;
    private $options = [];

    public function __construct($key, $data)
    {
        $this->key = $key;
        $this->name = $data['name'];
        $this->type = $data['type'];
        $this->options = $data['options'];
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    public function validate($value)
    {
        return count($value) > 2; // TODO: Add proper validation.
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
}