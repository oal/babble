<?php

namespace Babble;

use Babble\Exceptions\InvalidModelException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ContentLoader
{
    private $modelName;

    public function __construct($model)
    {
        $this->modelName = $model;

        $fs = new Filesystem();
        $isModel = $fs->exists('../models/' . $model . '.toml');
        if (!$isModel) {
            throw new InvalidModelException('Model does not exist.');
        }
    }

    public function find($id)
    {
        return $this->idToModel($id);
    }

    public function get()
    {
        $finder = new Finder();
        $files = $finder->files()->in($this->getModelDirectory());

        $result = [];
        foreach ($files as $file) {
            $id = $this->filenameToId($file->getFilename());
            $model = new Model($this->modelName, $id);
            $result[] = $model;
        }

        return $result;
    }


    /**
     * @param $path
     * @return null|Model
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
                $model = $loader->find($id);
                if ($model) return $model;
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
        return '../content/' . $this->modelName . '/';
    }

    private function idToModel(string $id): Model
    {
        $fs = new Filesystem();
        $dataFileExists = $fs->exists($this->getModelDirectory() . $id . '.toml');

        if ($dataFileExists) return new Model($this->modelName, $id);
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
