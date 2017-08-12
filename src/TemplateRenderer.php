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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;
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
        $loader = new ContentLoader($this->getModel());

        try {
            $record = $loader->find('index');
        } catch (RecordNotFoundException $e) {
            return null;
        }

        if ($record[$methodName]) {
            return $record[$methodName];
        }

        return call_user_func_array(array($record, $methodName), $args);
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
        $twig = new Twig_Environment($loader, ['debug' => true]);

        foreach ($this->resources as $modelName => $resource) {
            $twig->addGlobal($modelName, $resource);
        }

        $content = @file_get_contents(absPath('content/site.yaml'));
        if ($content !== false) {
            $siteData = Yaml::parse(file_get_contents(absPath('content/site.yaml')));
            $twig->addGlobal('site', $siteData);
        }

        if (class_exists('Aptoma\Twig\Extension\MarkdownExtension')) {
            $engineClass = 'Aptoma\Twig\Extension\MarkdownEngine\MichelfMarkdownEngine';
            $engine = new $engineClass;

            $extensionClass = 'Aptoma\Twig\Extension\MarkdownExtension';
            $twig->addExtension(new $extensionClass($engine));
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
     * @param string $path
     * @return Response
     */
    public function render(string $path)
    {
        // Allow trailing slash.
        if (substr($path, strlen($path) - 1) === '/') {
            $path = substr($path, 0, strlen($path) - 1);
        }

        $html = $this->renderTemplateFor($path);
        if ($html === null) $html = $this->renderRecordFor($path);
        if ($html === null && substr($path, -6) !== '/index') $html = $this->render($path . '/index');
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
     * @param string $path
     * @return string|null
     */
    function renderRecordFor(string $path)
    {
        $record = $this->pathToRecord($path);
        if ($record === null) return null;

        $basePath = self::pathToDir($path);
        $modelType = $record->getType();
        $templateFile = $basePath . '/' . $modelType . '.twig';

        $html = $this->twig->render($templateFile, [
            'this' => new TemplateRecord($record)
        ]);

        return $html;
    }

    /**
     * Renders a non-capitalized template matching the given path directly.
     *
     * @param string $path
     * @return string|null
     */
    function renderTemplateFor(string $path)
    {
        $isHidden = array_filter(explode('/', $path), function ($dir) {
            return strlen($dir) > 0 && $dir[0] === '_';
        });

        if (!$isHidden) {
            $templateFile = $path . '.twig';
            return $this->renderTemplate($templateFile);
        }

        return null;
    }

    private function renderTemplate(string $templateFile)
    {
        $fs = new Filesystem();
        if ($fs->exists(absPath('templates/' . $templateFile))) {
            $html = $this->twig->render($templateFile, []);
            return $html;
        }
        return null;
    }


    /**
     * @param $path
     * @return null|Record
     */
    private function pathToRecord(string $path)
    {
        $basePath = self::pathToDir($path);

        $templateFinder = new Finder();
        $templateFinder
            ->files()
            ->depth(0)
            ->name('/^[A-Z].+\.twig/')
            ->in(absPath('templates/' . $basePath));

        $id = substr($path, strlen($basePath) + 1);

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
     * @param string $path
     * @return string
     */
    public static function pathToDir(string $path): string
    {
        $basePath = substr($path, 0, strrpos($path, '/'));
        if ($basePath) {
            $fs = new Filesystem();
            while (strlen($basePath) > 0) {
                if ($fs->exists(absPath('templates/' . $basePath))) {
                    break;
                }
                $pathParts = explode('/', $basePath);
                array_pop($pathParts);
                $basePath = implode('/', $pathParts);
            }
        }
        return $basePath;
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
