<?php

namespace Babble;

use Babble\Content\ContentLoader;
use Babble\Events\RenderDependencyEvent;
use Babble\Events\RenderEvent;
use Babble\Exceptions\InvalidModelException;
use Babble\Exceptions\RecordNotFoundException;
use Babble\Models\TemplateRecord;
use Babble\Models\Model;
use Babble\Models\Record;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Twig_Environment;
use Twig_Loader_Filesystem;

class DependencyTrackedResource
{
    private $model;
    protected $wasAccessed = false;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * @return bool
     */
    public function wasAccessed(): bool
    {
        return $this->wasAccessed;
    }

    public function getModel(): Model
    {
        return $this->model;
    }
}

// Used if Model's isSingle() === true.
class SingleRecordDecorator extends DependencyTrackedResource
{
    public function __call($methodName, $args)
    {
        $this->wasAccessed = true;

        try {
            $record = Record::fromDisk($this->getModel());
        } catch (RecordNotFoundException $e) {
            return null;
        }

        $templateRecord = new TemplateRecord($record);
        if ($templateRecord[$methodName]) {
            return $templateRecord[$methodName];
        }

        return call_user_func_array(array($templateRecord, $methodName), $args);
    }

    /**
     * @return bool
     */
    public function wasAccessed(): bool
    {
        return $this->wasAccessed;
    }
}

// Decorate ContentLoader so querying models looks better (Post.where(...) vs Post().where(...)).
class ContentLoaderDecorator extends DependencyTrackedResource
{
    public function __call($methodName, $args)
    {
        $this->wasAccessed = true;
        $loader = new ContentLoader($this->getModel());
        // If all records are requested, just return the loader (an iterator)
        if ($methodName === 'all') return $loader;

        // Otherwise, call the correct method and return the result (for "where" etc, that will be the loader instance).
        return call_user_func_array(array($loader, $methodName), $args);
    }

}

class TemplateRenderer
{
    private $dispatcher;
    private $twig;
    private $resources;

    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->initResources();
        $this->initTwig();
    }

    private function initTwig()
    {
        $loader = new Twig_Loader_Filesystem(absPath('templates'));
        $twig = new Twig_Environment($loader, [
            'debug' => true,
            'autoescape' => false
        ]);

        foreach ($this->resources as $modelName => $resource) {
            $twig->addGlobal($modelName, $resource);
        }

        $this->twig = $twig;
    }

    public function renderNotFound()
    {
        return $this->renderTemplate('_404.twig');
    }

    /**
     * Finds the appropriate way to render the requested page, and returns a Response object.
     *
     * @param Path $path
     * @return string
     */
    public function render(Path $path)
    {
        $html = $this->renderTemplateFor($path);
        if ($html === null) $html = $this->renderRecordFor($path);
        if ($html === null && $path->getFilename() !== 'index') {
            $path = new Path($path . '/index');
            $html = $this->renderRecordFor($path);
        }
        if ($html === null) return null;

        // What ContentLoaders / Models were accessed during render?
        foreach ($this->resources as $modelName => $resource) {
            if ($resource->wasAccessed()) {
                $this->dispatcher->dispatch(
                    RenderDependencyEvent::NAME, new RenderDependencyEvent($modelName, $path)
                );
            }
        }

        $this->dispatcher
            ->dispatch(RenderEvent::NAME, new RenderEvent($path, $html));

        return $html;
    }

    /**
     * Looks for a matching record and capitalized template name and sets "this" to the matching Record
     * inside template context.
     *
     * @param Path $path
     * @return null|string
     */
    private function renderRecordFor(Path $path)
    {
        $record = $this->pathToRecord($path);
        if ($record === null) return null;

        $basePath = self::pathToDir($path);
        $modelType = $record->getType();
        $templateFile = $basePath . '/' . $modelType;

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if ($extension) $templateFile .= '.' . $extension;
        $html = $this->twig->render($templateFile . '.twig', [
            'this' => new TemplateRecord($record),
            'path' => $path
        ]);

        $this->dispatcher->dispatch(
            RenderDependencyEvent::NAME,
            new RenderDependencyEvent($record->getType(), $path)
        );

        return $html;
    }

    /**
     * Renders a non-capitalized template matching the given path directly.
     *
     * @param Path $path
     * @return null|string
     */
    private function renderTemplateFor(Path $path)
    {
        if ($path->isHidden()) {
            return null;
        }

        $templateDir = pathToTemplateDir($path);
        $path->bindRoute($templateDir);
        $templateFile = $templateDir . '/' . $path->getFilename() . '.twig';

        $context = ['path' => $path];
        $html = $this->renderTemplate($templateFile, $context);

        // Try index file inside a directory with the name of this path's filename unless filename is index.
        if (!$html && !$path->getExtension() && $path->getFilename() !== 'index') {
            $templateFile = $path . '/index.twig';
            $html = $this->renderTemplate($templateFile, $context);
        }
        return $html;
    }

    private function renderTemplate(string $templateFile, array $context = [])
    {
        $fs = new Filesystem();
        if ($fs->exists(absPath('templates/' . $templateFile))) {
            $html = $this->twig->render($templateFile, $context);
            return $html;
        }
        return null;
    }

    /**
     * @param Path $path
     * @return null|Record
     */
    private function pathToRecord(Path $path)
    {
        $basePath = self::pathToDir($path);

        $templateFinder = new Finder();
        $templateFinder
            ->files()
            ->depth(0)
            ->name('/^[A-Z].+\.twig/')
            ->in(absPath('templates/' . $basePath));

        $idParts = explode('/', $path->getWithoutExtension());
        $id = end($idParts);

        foreach ($templateFinder as $file) {
            $modelNameMaybe = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            try {
                $model = new Model($modelNameMaybe);
                $record = Record::fromDisk($model, $id);
                if ($record) return $record;
            } catch (InvalidModelException $e) {
            } catch (RecordNotFoundException $e) {
            }
        }

        return null;
    }

    /**
     * Takes the path from a request and finds its template base directory.
     *
     * @param Path $path
     * @return string
     */
    public static function pathToDir(Path $path): string
    {
        return pathToTemplateDir($path);
    }

    private function initResources()
    {
        $modelNames = ContentLoader::getModelNames();
        $resources = [];
        foreach ($modelNames as $modelName) {
            $model = new Model($modelName);
            if ($model->isSingle()) {
                $record = new SingleRecordDecorator($model);
                $resources[$modelName] = $record;
            } else {
                $loader = new ContentLoaderDecorator($model);
                $resources[$modelName] = $loader;
            }
        }

        $this->resources = $resources;
    }
}

function pathToTemplateDir(string $path, string $discoveredPath = '')
{
    $currentPath = absPath('templates/' . $discoveredPath);

    $pathSplit = explode('/', trim($path, '/'), 2);
    if (count($pathSplit) === 1) {
        return $discoveredPath;
    }

    $finder = new Finder();
    $varDirs = $finder
        ->in($currentPath)
        ->name('/^[$].+/')
        ->directories()
        ->depth(0);

    $path = $pathSplit[1];
    foreach ($varDirs as $varDir) {
        return pathToTemplateDir($path, $discoveredPath . '/' . $varDir->getBasename());
    }

    $possibleExactMatch = $discoveredPath . '/' . $pathSplit[0] . '.twig';
    if (file_exists(absPath('templates/' . $possibleExactMatch))) {
        return $discoveredPath;
    }

    $dirHasModelFiles = $finder
            ->in(absPath('templates/' . $discoveredPath))
            ->files()
            ->name('/^[A-Z].+(.twig)$/')
            ->depth(0)->count() > 0;

    if ($dirHasModelFiles) {
        return $discoveredPath;
    }

    return null;
}