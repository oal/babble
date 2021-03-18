<?php

namespace Babble\Models;

use Babble\Exceptions\InvalidModelException;
use Babble\Models\Fields\ListField;
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

    protected $properties;

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
        $this->initProperties($modelFormat['properties'] ?? []);
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
        } else if (substr($this->name, strlen($this->name) - 1) === 's') {
            $this->namePlural = $this->name;
        } else {
            $this->namePlural = $this->name . 's';
        }
    }

    protected function initFields($fields)
    {
        foreach ($fields as $key => $data) {
            $type = $data['type'];
            $fieldClass = self::getFieldRegistry()->get($type);
            $this->fields[$key] = new $fieldClass($this, $key, $data);
        }
    }

    public function jsonSchema()
    {
        $requiredFields = [];
        $fieldsSchema = [];
        foreach ($this->fields as $key => $field) {
            if ($field->isRequired()) {
                $requiredFields[] = $key;
            }
            $schema = $field->jsonSchema();
            if (!$schema) continue;
            $fieldsSchema[$key] = $schema;
        }

        return [
            '$schema' => 'http://json-schema.org/draft-04/schema',
            'type' => 'object',
            'required' => ['fields'],
            'properties' => [
                'fields' => [
                    'type' => 'object',
                    'required' => $requiredFields,
                    'properties' => $fieldsSchema
                ]
            ]
        ];
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

    public function getBlocks()
    {
        $blocks = [];
        foreach ($this->getFields() as $field) {
            if (get_class($field) !== ListField::class) continue;
            $blocks = array_merge($blocks, $field->getBlocks());
        }

        // Avoid loading blocks for List fields twice (keep track of already processed).
        $processedTypes = [$this->getType()];
        foreach ($blocks as $block) {
            if (!in_array($block->getType(), $processedTypes)) {
                $blocks = array_merge($blocks, $block->getBlocks());
            }
        }

        return $blocks;
    }

    public function getCacheLocation(string $recordId)
    {
        return '/uploads/_cache/';
    }

    protected function initProperties(array $properties)
    {
        // TODO: Validation.
        $loader = new \Twig\Loader\ArrayLoader($properties);
        $twig = new \Twig\Environment($loader);
        $this->properties = $twig;
    }

    public function getProperty(string $property, $context)
    {
        return $this->properties->render($property, $context);
    }

    public function hasProperty(string $property)
    {
        return $this->properties->getLoader()->exists($property);
    }
}

