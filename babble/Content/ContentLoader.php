<?php

namespace Babble\Content;

use Babble\Exceptions\InvalidModelException;
use Babble\Record;
use Babble\Models\Model;
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
        return $this->idToRecord($id);
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
        $files = $finder->files()->in($this->getModelDirectory());

        $result = [];
        foreach ($files as $file) {
            $id = $this->filenameToId($file->getFilename());
            $record = Record::fromDisk($this->model, $id);
            if (!$this->filters->isMatch($record)) continue;
            $result[] = $record;
        }

        return $result;
    }


    /**
     * @param $path
     * @return null|Record
     */
    static function matchPath(string $path)
    {
        $basePath = substr($path, 0, strrpos($path, '/'));

        $id = substr($path, strrpos($path, '/') + 1);
        if (empty($id)) $id = 'index';

        $templateFinder = new Finder();
        $templateFinder->files()->depth('== 0')->in('../templates/' . $basePath);

        foreach ($templateFinder as $file) {
            $modelNameMaybe = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            try {
                $loader = new ContentLoader($modelNameMaybe);
                $record = $loader->find($id);
                if ($record) return $record;
            } catch (InvalidModelException $e) {
            }
        }

        return null;
    }

    /**
     * @return string
     */
    private function getModelDirectory(): string
    {
        return '../content/' . $this->model->getType() . '/';
    }

    private function idToRecord(string $id)
    {
        $fs = new Filesystem();
        $dataFileExists = $fs->exists($this->getModelDirectory() . $id . '.yaml');

        if ($dataFileExists) return Record::fromDisk($this->model, $id);
        return null;
    }

    private function filenameToId(string $filename): string
    {
        return pathinfo($filename, PATHINFO_FILENAME);
    }

    static function getModelNames()
    {
        $models = [];
        $finder = new Finder();
        $files = $finder->files()->in('../models/');

        foreach ($files as $filename) {
            $modelName = pathinfo($filename, PATHINFO_FILENAME);
            $models[] = $modelName;
        }

        return $models;
    }
}
