<?php

namespace Babble\Content;

use Babble\Exceptions\RecordNotFoundException;
use Babble\Models\ArrayAccessRecord;
use Babble\Models\Record;
use Babble\Models\Model;
use Imagine\Exception\InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ContentLoader
{
    private $model;
    private $filters;

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

        if ($dataFileExists) return new ArrayAccessRecord(Record::fromDisk($this->model, $id));
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

    public function get()
    {
        $finder = new Finder();
        try {
            $finder->files()->name('*.yaml')
                ->in($this->getModelDirectory());

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
            $record = Record::fromDisk($this->model, $id);
            if (!$this->filters->isMatch($record)) continue;
            $result[] = new ArrayAccessRecord($record);
        }

        return $result;
    }

    /**
     * @return string
     */
    private function getModelDirectory(): string
    {
        return '../content/' . $this->model->getType() . '/';
    }

    private function filenameToId(string $filename): string
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        return substr($filename, 0, strlen($filename) - strlen($ext) - 1);
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
