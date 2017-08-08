<?php

namespace Babble\Models;

use Babble\Exceptions\InvalidModelException;
use Babble\Models\Fields\BooleanField;
use Babble\Models\Fields\DatetimeField;
use Babble\Models\Fields\FileField;
use Babble\Models\Fields\ImageField;
use Babble\Models\Fields\ListField;
use Babble\Models\Fields\PasswordField;
use Babble\Models\Fields\TextField;
use JsonSerializable;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class BaseModel implements JsonSerializable
{
    protected $type;

    protected $name;
    protected $namePlural;

    protected $fields = [];

    public function __construct(string $type)
    {
        try {
            $this->init($type);
        } catch (ParseException $e) {
            throw new InvalidModelException('Invalid definition: ' . $type);
        }
    }

    protected function getDefinitionFile()
    {
        return absPath('models/blocks/' . $this->type . '.yaml');
    }

    protected function init($type)
    {
        $this->type = $type;

        $definitionFile = $this->getDefinitionFile();

        $fs = new Filesystem();
        if (!$fs->exists($definitionFile)) throw new InvalidModelException('Invalid block: ' . $type);

        $modelFormat = Yaml::parse(file_get_contents($definitionFile));

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
        return array_values($this->fields);
    }

    public function getField(string $key)
    {
        return $this->fields[$key];
    }

    protected function initName(array $modelFormat)
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

    protected function initFields($fields)
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
                case 'file':
                    $this->fields[$key] = new FileField($this, $key, $data);
                    break;
                case 'image':
                    $this->fields[$key] = new ImageField($this, $key, $data);
                    break;
                case 'password':
                    $this->fields[$key] = new PasswordField($this, $key, $data);
                    break;
                case 'list':
                    $this->fields[$key] = new ListField($this, $key, $data);
                    break;
            }
        }
    }

    public function jsonSerialize()
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'name_plural' => $this->namePlural,
            'fields' => $this->getFields()
        ];
    }

    public function getCacheLocation(string $recordId)
    {
        return '/uploads/_cache/';
    }
}

