<?php

namespace Babble\Models;

use Babble\Exceptions\InvalidModelException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class Model extends BaseModel
{
    private $hierarchical = false;
    private $single = false;
    private $options = [];

    protected function getDefinitionFile()
    {
        return absPath('models/' . $this->type . '.yaml');
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
        $this->single = ($modelFormat['single'] ?? false) === true;
        $this->initOptions($modelFormat);
        $this->initFields($modelFormat['fields']);
        $this->initProperties($modelFormat['properties'] ?? []);
    }

    public function exists(string $id = null): bool
    {
        $fs = new Filesystem();
        if ($this->isSingle()) {
            return $fs->exists(absPath('content/' . $this->getType() . '.yaml'));
        } else {
            if (!$id) return false;
            return $fs->exists(absPath('content/' . $this->getType() . '/' . $id . '.yaml'));
        }
    }

    static function all(): array
    {
        $finder = new Finder();
        $files = $finder->files()->depth(0)->in(absPath('models'));

        $models = [];
        foreach ($files as $file) {
            $modelName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $models[] = new Model($modelName);
        }

        usort($models, function ($a, $b) {
            if ($a->getName() < $b->getName()) return -1;
            if ($a->getName() > $b->getName()) return 1;
            return 0;
        });

        return $models;
    }

    private function initOptions(array $modelFormat)
    {
        if (empty($modelFormat['options'])) return;

        $options = $modelFormat['options'];
        if (is_array($options)) $this->options = $options;
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['options'] = $this->options;
        $data['hierarchical'] = $this->isHierarchical();
        $data['single'] = $this->isSingle();
        return $data;
    }

    public function isHierarchical(): bool
    {
        return $this->hierarchical;
    }

    public function isSingle(): bool
    {
        return $this->single;
    }

    public function getCacheLocation(string $recordId): string
    {
        $baseLocation = parent::getCacheLocation($recordId);
        return $baseLocation . $this->getType() . '/' . $recordId . '/';
    }

    public function getBaseURL(): string
    {
        $finder = new Finder();

        $type = $this->getType();
        $finder->in(absPath('templates'))->name("/^$type\..*twig/");

        $minLength = 9999999;
        $shortestFile = null;
        foreach ($finder as $file) {
            $length = strlen($file->getRelativePathname());
            if ($length < $minLength) {
                $minLength = $length;
                $shortestFile = $file;
            }
        }

        if (!$shortestFile) return '/';
        return '/' . $shortestFile->getRelativePath();
    }

    public function jsonSchema(): array
    {
        $schema = parent::jsonSchema();

        if (!$this->isSingle()) {
            $validIdCharacters = $this->getValidIdCharacters();
            $schema['required'][] = 'id';
            $schema['properties']['id'] = [
                'type' => 'string',
                'minLength' => 1,
                'pattern' => "^([$validIdCharacters]+)$"
            ];
        }
        return $schema;
    }

    /**
     * @return string
     */
    private function getValidIdCharacters(): string
    {
        $validIdCharacters = 'a-z0-9-_';
        if ($this->isHierarchical()) {
            $validIdCharacters .= '\/';
        }
        return $validIdCharacters;
    }
}

