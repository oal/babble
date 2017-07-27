<?php

namespace Babble\Content;

use Babble\Exceptions\InvalidModelException;
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
            $files = $finder
                ->files()
                ->depth(0)
                ->name('*.yaml')
                ->in($this->getModelDirectory());
        } catch (InvalidArgumentException $e) {
            return [];
        }

        $result = [];
        foreach ($files as $file) {
            $id = $this->filenameToId($file->getFilename());
            $record = Record::fromDisk($this->model, $id);
            if (!$this->filters->isMatch($record)) continue;
            $result[] = new ArrayAccessRecord($record);
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
        $templateFinder
            ->files()
            ->depth(0)
            ->name('/^[A-Z].+\.twig/')
            ->in('../templates/' . $basePath);


        foreach ($templateFinder as $file) {
            $modelNameMaybe = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            try {
                $loader = new ContentLoader($modelNameMaybe);
                $record = $loader->find($id);
                if ($record) return $record;
            } catch (InvalidModelException $e) {
            } catch (RecordNotFoundException $e) {
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

    private function filenameToId(string $filename): string
    {
        return pathinfo($filename, PATHINFO_FILENAME);
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
