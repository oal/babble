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

class Model extends Block
{
    private $hierarchical;
    private $options = [];

    protected function getDefinitionFile()
    {
        return '../models/' . $this->type . '.yaml';
    }

    protected function init($type)
    {
        $this->type = $type;

        $definitionFile = $this->getDefinitionFile();

        $fs = new Filesystem();
        if (!$fs->exists($definitionFile)) throw new InvalidModelException('Invalid model: ' . $type);

        $modelFormat = Yaml::parse(file_get_contents($definitionFile));

        $this->initName($modelFormat);
        $this->hierarchical = ($modelFormat['hierarchical'] ?? false) === true;
        $this->initOptions($modelFormat);
        $this->initFields($modelFormat['fields']);
    }

    public function exists(string $id)
    {
        $fs = new Filesystem();
        return $fs->exists('../content/' . $this->getType() . '/' . $id . '.yaml');
    }

    static function all()
    {
        $finder = new Finder();
        $files = $finder->files()->depth(0)->in('../models');

        $models = [];
        foreach ($files as $file) {
            $modelName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $models[] = new Model($modelName);
        }

        return $models;
    }

    private function initOptions(array $modelFormat)
    {
        if (empty($modelFormat['options'])) return;

        $options = $modelFormat['options'];
        if (is_array($options)) $this->options = $options;
    }

    public function jsonSerialize()
    {
        $data = parent::jsonSerialize();
        $data['options'] = $this->options;
        return $data;
    }


    public function isHierarchical()
    {
        return $this->hierarchical;
    }
}

