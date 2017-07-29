<?php

namespace Babble\Content;

use Babble\Exceptions\RecordNotFoundException;
use Babble\Models\TemplateRecord;
use Babble\Models\Record;
use Babble\Models\Model;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ContentLoader
{
    private $model;
    private $filters;
    private $withChildren = false;
    private $parentId;

    public function __construct(string $modelType)
    {
        $this->model = new Model($modelType);
        $this->filters = new FilterContainer();
    }

    public function find($id)
    {
        $fs = new Filesystem();
        $filePath = $this->getModelDirectory() . $id . '.yaml';
        $dataFileExists = $fs->exists($filePath);

        if ($dataFileExists) return new TemplateRecord(Record::fromDisk($this->model, $id));
        throw new RecordNotFoundException($this->model->getType() . ' record with ID "' . $id . '" does not exist.');
    }

    public function where($key, $comparison, $value)
    {
        // TODO: Validate that model actually has the key / column provided.
        $this->filters->and(new WhereFilter($key, $comparison, $value));
        return $this;
    }

    public function orWhere($key, $comparison, $value)
    {
        // TODO: Validate that model actually has the key / column provided.
        $this->filters->or(new WhereFilter($key, $comparison, $value));
        return $this;
    }

    public function withChildren()
    {
        // TODO: Warn if not hierarchical?
        $this->withChildren = true;
        return $this;
    }

    public function childrenOf(string $id)
    {
        // TODO: Warn if not hierarchical?
        $this->parentId = $id;
        return $this;
    }

    public function get()
    {
        $finder = new Finder();
        try {
            $finder->files()->name('*.yaml')
                ->in($this->getModelDirectory());

            if (!$this->withChildren) {
                $finder->depth(0);
            }

            // Can model have child records?
            if (!$this->model->isHierarchical()) {
                $finder->depth(0);
            }
        } catch (InvalidArgumentException $e) {
            return [];
        }
        $result = [];
        foreach ($finder as $file) {
            $id = $this->filenameToId($file->getRelativePathname());
            error_log($id);
            $record = Record::fromDisk($this->model, $id);
            if (!$this->filters->isMatch($record)) continue;
            $result[] = new TemplateRecord($record);
        }

        return $result;
    }

    /**
     * @return string
     */
    private function getModelDirectory(): string
    {
        $type = $this->model->getType();
        $path = '../content/' . $type . '/';

        // Add parent ID to loader path.
        if ($this->parentId) {
            $path .= $this->parentId . '/';
        }

        return $path;
    }

    private function filenameToId(string $filename): string
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $id = substr($filename, 0, strlen($filename) - strlen($ext) - 1);

        // Add parent ID.
        if($this->parentId) {
            $id = $this->parentId . '/' . $id;
        }

        return $id;
    }

    static function getModelNames()
    {
        $models = [];
        $finder = new Finder();
        $files = $finder
            ->files()
            ->depth(0)
            ->name('/^[A-Z].+\.yaml$/')
            ->in('../models/');

        foreach ($files as $filename) {
            $modelName = pathinfo($filename, PATHINFO_FILENAME);
            $models[] = $modelName;
        }

        return $models;
    }
}
