<?php

namespace Babble;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ContentLoader
{
    /**
     * @param $path
     * @return null|Model
     */
    function matchPath(string $path)
    {
        $basePath = substr($path, 0, strrpos($path, '/'));

        $id = substr($path, strrpos($path, '/') + 1);
        if(empty($id)) $id = 'index';

        $templateFinder = new Finder();
        $templateFinder->files()->in('../templates' . $basePath);

        $models = $this->getModelNames();
        $fs = new Filesystem();

        foreach ($templateFinder as $file) {
            $modelNameMaybe = pathinfo($file->getFilename(), PATHINFO_FILENAME);

            if (in_array($modelNameMaybe, $models)) {
                $dataFileExists = $fs->exists('../content/' . $modelNameMaybe . '/' . $id . '.toml');
                if ($dataFileExists) return new Model($modelNameMaybe, $id);
            }
        }

        return null;
    }

    private function getModelNames()
    {
        $finder = new Finder();
        $finder->files()->in('../models');

        $models = [];
        foreach ($finder as $file) {
            $cleanName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $models[] = $cleanName;
        }

        return $models;
    }
}