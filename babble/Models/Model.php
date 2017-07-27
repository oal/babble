<?php

namespace Babble\Models;

use Babble\Exceptions\InvalidModelException;
use Babble\Models\Fields\BooleanField;
use Babble\Models\Fields\DatetimeField;
use Babble\Models\Fields\Field;
use Babble\Models\Fields\ImageField;
use Babble\Models\Fields\PasswordField;
use Babble\Models\Fields\TextField;
use JsonSerializable;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

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

        $modelFile = '../models/' . $modelType . '.yaml';

        $fs = new Filesystem();
        if (!$fs->exists($modelFile)) throw new InvalidModelException('Invalid model: ' . $modelType);

        $modelFormat = Yaml::parse(file_get_contents($modelFile));

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
        return array_values($this->fields);
    }

    function jsonSerialize()
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'name_plural' => $this->namePlural,
            'options' => $this->options,
            'fields' => $this->getFields()
        ];
    }

    public function exists(string $id)
    {
        $fs = new Filesystem();
        return $fs->exists('../content/' . $this->getType() . '/' . $id . '.yaml');
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
        // TODO: Add a field registry and do this dynamically.
        foreach ($fields as $key => $data) {
            switch ($data['type']) {
                case 'text':
                    $this->fields[$key] = new TextField($this, $key, $data);
                    break;
                case 'boolean':
                    $this->fields[$key] = new BooleanField($this, $key, $data);
                    break;
                case 'datetime':
                    $this->fields[$key] = new DatetimeField($this, $key, $data);
                    break;
                case 'image':
                    $this->fields[$key] = new ImageField($this, $key, $data);
                    break;
                case 'password':
                    $this->fields[$key] = new PasswordField($this, $key, $data);
                    break;
            }
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

    public function hasField($key): bool
    {
        return array_key_exists($key, $this->fields);
    }

    public function getField($key): Field
    {
        if (!array_key_exists($key, $this->fields)) return null;
        return $this->fields[$key];
    }
}

