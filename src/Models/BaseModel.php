<?php

namespace Babble\Models;

use Babble\Exceptions\InvalidModelException;
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
    private static $fieldRegistry;

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
     * @return FieldRegistry
     */
    public static function getFieldRegistry()
    {
        if (!self::$fieldRegistry) {
            self::$fieldRegistry = new FieldRegistry();
        }

        return self::$fieldRegistry;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
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
        foreach ($fields as $key => $data) {
            $type = $data['type'];

            // TODO: Hard coded only two levels deep.
            if ($type === 'list' &&
                get_class($this) === Block::class &&
                get_class($this->getModel()) === Block::class &&
                get_class($this->getModel()->getModel()) === Model::class) {
                break;
            }

            $fieldClass = self::getFieldRegistry()->get($type);
            $this->fields[$key] = new $fieldClass($this, $key, $data);
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

