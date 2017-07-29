<?php

namespace Babble;

use Babble\Content\ContentLoader;
use Babble\Exceptions\InvalidModelException;
use Babble\Exceptions\RecordNotFoundException;
use Babble\Models\ArrayAccessRecord;
use Babble\Models\Model;
use Babble\Models\Record;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Twig_Environment;
use Twig_Function;
use Twig_Loader_Filesystem;

class TemplateRenderer
{
    private $twig;

    public function __construct()
    {
        $this->initTwig();
    }

    private function initTwig()
    {
        $loader = new Twig_Loader_Filesystem('../templates');
        $twig = new Twig_Environment($loader, ['debug' => true]);

        $modelNames = ContentLoader::getModelNames();
        foreach ($modelNames as $modelName) {
            $twig->addFunction(new Twig_Function($modelName, function () use ($modelName) {
                return new ContentLoader($modelName);
            }));
        }

        $this->twig = $twig;
    }

    /**
     * Finds the appropriate way to render the requested page, and returns a Response object.
     *
     * @param string $path
     * @return Response
     */
    public function render(string $path)
    {
        $html = $this->renderRecord($path);
        if ($html === null) $html = $this->renderTemplate($path);
        if ($html === null) return new Response('404', 404); // TODO: Render custom 404 page.
        return new Response($html);
    }

    /**
     * Looks for a matching record and capitalized template name and sets "this" to the matching Record
     * inside template context.
     *
     * @param string $path
     * @return string|null
     */
    function renderRecord(string $path)
    {
        $record = $this->pathToRecord($path);
        if ($record === null) return null;

        $basePath = self::pathToDir($path);
        $modelType = $record->getType();
        $templateFile = $basePath . '/' . $modelType . '.twig';

        $html = $this->twig->render($templateFile, [
            'this' => new ArrayAccessRecord($record)
        ]);

        return $html;
    }

    /**
     * Renders a non-capitalized template matching the given path directly.
     *
     * @param string $path
     * @return string|null
     */
    function renderTemplate(string $path)
    {
        $isHidden = array_filter(explode('/', $path), function ($dir) {
            return strlen($dir) > 0 && $dir[0] === '_';
        });

        if (!$isHidden) {
            $templateFile = $path . '.twig';

            $fs = new Filesystem();
            if ($fs->exists('../templates' . $templateFile)) {
                $html = $this->twig->render($templateFile, []);
                return $html;
            }
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
            ->in('../templates/' . $basePath);

        $id = substr($path, strlen($basePath) + 1);
        if (empty($id)) $id = 'index';

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
                if ($fs->exists('../templates/' . $basePath)) {
                    break;
                }
                $pathParts = explode('/', $basePath);
                array_pop($pathParts);
                $basePath = implode('/', $pathParts);
            }
        }
        return $basePath;
    }
}
