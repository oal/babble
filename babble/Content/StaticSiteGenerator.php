<?php

namespace Babble\Content;

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
        $finder = new Finder();
        $fs->remove($finder->in(absPath('build')));

        // Find all pages to be built.
        $finder = new Finder();
        foreach ($finder->in('templates') as $file) {
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
        $this->save($path, $this->renderer->renderTemplateFor($path));
    }

    private function renderRecord($relativePath)
    {
        $directory = pathinfo($relativePath, PATHINFO_DIRNAME);
        if ($directory === '.') $directory = '';
        else $directory = '/' . $directory;

        $modelName = pathinfo($relativePath, PATHINFO_FILENAME);
        $loader = new ContentLoader($modelName);
        foreach ($loader as $record) {
            $path = $directory . '/' . $record['id'];
            $this->save($path, $this->renderer->renderRecordFor($path));
        }
    }

    private function save($path, $html)
    {
        $fs = new Filesystem();
        $parts = explode('/', $path);

        if ($parts[count($parts) - 1] === 'index') {
            $targetFile = $path . '.html';
        } else {
            $targetFile = $path . '/index.html';
        }

        $fs->dumpFile(absPath('build/' . $targetFile), $html);
    }

    private function getRelativePath(SplFileInfo $file): string
    {
        $filename = $file->getFilename();
        $dir = substr($file->getPath(), strlen('templates') + 1);

        if ($dir) $relativePath = $dir . '/' . $filename;
        else $relativePath = $filename;

        return $relativePath;
    }
}