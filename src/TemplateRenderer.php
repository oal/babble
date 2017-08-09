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

// Decorate ContentLoader so querying models looks better (Post.where(...) vs Post().where(...)).
class ContentLoaderDecorator
{
    private $modelType;
    private $wasAccessed = false;

    public function __construct(string $modelType)
    {
        $this->modelType = $modelType;
    }

    public function __call($method_name, $args)
    {
        $this->wasAccessed = true;
        $loader = new ContentLoader($this->modelType);
        // If all records are requested, just return the loader (an iterator)
        if ($method_name === 'all') return $loader;

        // Otherwise, call the correct method and return the result (for "where" etc, that will be the loader instance).
        return call_user_func_array(array($loader, $method_name), $args);
    }

    /**
     * @return bool
     */
    public function wasAccessed(): bool
    {
        return $this->wasAccessed;
    }
}

class TemplateRenderer
{
    private $dispatcher;
    private $twig;
    private $loaders;

    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->initContentLoaders();
        $this->initTwig();
    }

    private function initTwig()
    {
        $loader = new Twig_Loader_Filesystem(absPath('templates'));
        $twig = new Twig_Environment($loader, ['debug' => true]);

        foreach ($this->loaders as $modelName => $loader) {
            $twig->addGlobal($modelName, $loader);
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

    private function renderIndex(string $path)
    {
        if (substr($path, strlen($path) - 1) === '/') $path .= '/index';
        $this->render($path);
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
        if ($html === null) $html = $this->render($path . '/index');
        if ($html === null) return null;

        // What ContentLoaders / Models were accessed during render?
        foreach ($this->loaders as $modelName => $loader) {
            if ($loader->wasAccessed()) {
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

    private function initContentLoaders()
    {
        $modelNames = ContentLoader::getModelNames();
        $loaders = [];
        foreach ($modelNames as $modelName) {
            $loader = new ContentLoaderDecorator($modelName);
            $loaders[$modelName] = $loader;
        }

        $this->loaders = $loaders;
    }
}
