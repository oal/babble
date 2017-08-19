<?php

namespace Babble\Content;

use Babble\Models\Model;
use Babble\Path;
use Babble\TemplateRenderer;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class StaticSiteGenerator
{
    private $renderer;

    public function __construct()
    {
        $dispatcher = new EventDispatcher();
        $this->renderer = new TemplateRenderer($dispatcher);
    }


    public function build()
    {
        $fs = new Filesystem();

        // Remove all previous build files.
        if ($fs->exists(absPath('build'))) {
            $finder = new Finder();
            $fs->remove($finder->in(absPath('build')));
        }

        // Find all pages to be built.
        $finder = new Finder();
        foreach ($finder->in(absPath('templates')) as $file) {
            if ($file->isDir()) continue;

            $filename = $file->getFilename();
            $relativePath = $this->getRelativePath($file);

            // Build pages.
            $firstChar = substr($filename, 0, 1);
            if ($firstChar === '_') {
                // TODO: What to do with _404.twig etc?
            } else if ($firstChar === strtoupper($firstChar)) {
                $this->renderRecord($relativePath);
            } else {
                $this->renderPage($relativePath);
            }
        }

        // Create symlinks to static assets and uploads.
        $fs->symlink(absPath('public/static'), absPath('build/static'));
        $fs->symlink(absPath('public/uploads'), absPath('build/uploads'));
    }

    private function renderPage(string $relativePath)
    {
        $extLength = strlen(pathinfo($relativePath, PATHINFO_EXTENSION));
        $path = '/' . substr($relativePath, 0, strlen($relativePath) - $extLength - 1); // -1 for the slash.
        $pathObject = new Path($path);
        $this->save($pathObject, $this->renderer->render($pathObject));
    }

    private function renderRecord($relativePath)
    {
        $directory = pathinfo($relativePath, PATHINFO_DIRNAME);
        if ($directory === '.') $directory = '';
        else $directory = '/' . $directory;

        $extension = pathinfo(substr($relativePath, 0, -5), PATHINFO_EXTENSION);
        $modelName = pathinfo($relativePath, PATHINFO_FILENAME);
        if($extension) {
            $extensionLength = strlen($extension)+1;
            $modelName = substr($modelName, 0, -$extensionLength);
        }

        $model = new Model($modelName);
        $loader = new ContentLoader($model);
        $loader = $loader->withChildren();
        foreach ($loader as $record) {
            $path = $directory . '/' . $record['id'];
            if ($extension) $path .= '.' . $extension;
            $pathObject = new Path($path);
            echo $pathObject . "\n";
            $this->save($pathObject, $this->renderer->render($pathObject));
        }
    }

    private function save(Path $path, $html)
    {
        $fs = new Filesystem();

        if($path->getExtension()) {
            $targetFile = $path;
        } else if ($path->getFilename() === 'index') {
            $targetFile = $path . '.html';
        } else {
            $targetFile = $path . '/index.html';
        }

        $fs->dumpFile(absPath('build/' . $targetFile), $html);
    }

    private function getRelativePath(SplFileInfo $file): string
    {
        $filename = $file->getFilename();
        $dir = explode('/templates', $file->getPath())[1];

        if ($dir) $relativePath = $dir . '/' . $filename;
        else $relativePath = $filename;

        return $relativePath;
    }
}