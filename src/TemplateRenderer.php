<?php

namespace Babble;

use Babble\Content\ContentLoader;
use Babble\Events\RenderDependencyEvent;
use Babble\Events\RenderEvent;
use Babble\Exceptions\RecordNotFoundException;
use Babble\Models\TemplateRecord;
use Babble\Models\Model;
use Babble\Models\Record;
use Cocur\Slugify\Bridge\Twig\SlugifyExtension;
use Cocur\Slugify\Slugify;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Filesystem;
use Twig\TwigFunction;

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
        if ($templateRecord[$methodName] ?? null) {
            return $templateRecord[$methodName];
        }

        if (method_exists($templateRecord, $methodName) && is_callable(array($templateRecord, $methodName))) {
            return call_user_func_array(array($templateRecord, $methodName), $args);
        }
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

class TemplateAbortException extends \Exception
{
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
        $loader = new \Twig\Loader\FilesystemLoader(absPath('templates'));
        $twig = new \Twig\Environment($loader, [
            'debug' => true,
            'autoescape' => false
        ]);

        foreach ($this->resources as $modelName => $resource) {
            $twig->addGlobal($modelName, $resource);
        }

        $twig->addFunction(new TwigFunction('abort', function ($a) {
            throw new TemplateAbortException();
        }, ['needs_environment' => true]));

        $twig->addExtension(new SlugifyExtension(Slugify::create()));

        $this->twig = $twig;
    }

    public function renderNotFound()
    {
        return $this->renderTemplate('/_404.twig');
    }

    /**
     * Finds the appropriate way to render the requested page, and returns a Response object.
     *
     * @param Path $path
     * @return string
     * @throws \Twig\Error\RuntimeError
     */
    public function render(Path $path)
    {
        $html = null;
        $t = new TemplateLocator($path);
        foreach ($t->next() as $template) {
            $context = $this->buildContext($path, $template);

            // Skip template if no valid context could be created.
            if ($context === null) continue;

            // If abort() wasn't called in template, and it returned some HTML, we'll break and return this.
            $html = $this->renderOrAbort($template, $context);
            if ($html !== null) break;
        }
        if ($html === null) {
            // If no match is found, it might be that we're looking for an "index" model, so append "/index" adn try
            // again.
            $pathWithoutExt = explode('/', $path->getWithoutExtension());
            $fileWithoutExt = end($pathWithoutExt);
            if ($fileWithoutExt !== 'index') {
                return $this->render(new Path($path . '/index'));
            }

            // Nope, this really is a 404.
            return null;
        }

        // What ContentLoaders / Models were accessed during render?
        foreach ($this->resources as $modelName => $resource) {
            if ($resource->wasAccessed()) {
                $this->dispatcher->dispatch(
                    new RenderDependencyEvent($modelName, $path), RenderDependencyEvent::NAME
                );
            }
        }

        $this->dispatcher
            ->dispatch(new RenderEvent($path, $html), RenderEvent::NAME);

        return $html;
    }

    private function renderOrAbort(Template $template, array $context)
    {
        try {
            $html = $this->renderTemplate($template->getTemplatePath(), $context);
        } catch (\Twig\Error\RuntimeError $e) {
            // Re-throw if not TemplateAbortException (something else failed)
            if (!($e->getPrevious() instanceof TemplateAbortException)) {
                throw $e;
            }
            // Nullify $html if TemplateAbortException so we get a 404 instead.
            $html = null;
        }
        return $html;
    }

    private function renderTemplate(string $templateFile, array $context = [])
    {
        $fs = new Filesystem();
        if ($fs->exists(absPath('templates' . $templateFile))) {
            $html = $this->twig->render($templateFile, $context);
            return $html;
        }
        return null;
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

    private function buildContext(Path $path, Template $template)
    {
        $context = [
            'path' => $template->getBoundPath()
        ];

        if ($template->hasModel()) {
            $record = $template->getRecord();
            if ($record !== null) {
                $context['this'] = new TemplateRecord($record);
                $this->dispatcher->dispatch(
                    new RenderDependencyEvent($record->getType(), $path),
                    RenderDependencyEvent::NAME
                );
            } else {
                return null;
            }
        }

        return $context;
    }
}
