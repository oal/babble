<?php

namespace Babble;

use Babble\Content\ContentLoader;
use Babble\Models\ArrayAccessRecord;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Twig_Environment;
use Twig_Function;
use Twig_Loader_Filesystem;

class TemplateRenderer
{
    private $twig;
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
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

    function renderRecord(ArrayAccessRecord $record)
    {
        $path = $this->request->getPathInfo();
        // TODO: Extract (same as in ContentLoader)
        $basePath = substr($path, 0, strrpos($path, '/'));
        if ($basePath) {
            $fs = new Filesystem();
            while (strlen($basePath) > 0) {
                if($fs->exists('../templates/' . $basePath)) {
                    break;
                }
                $pathParts = explode('/', $basePath);
                array_pop($pathParts);
                $basePath = implode('/', $pathParts);
            }
        }

        $modelType = $record->getType();
        $templateFile = $basePath . '/' . $modelType . '.twig';
        return $this->twig->render($templateFile, [
            'this' => $record
        ]);
    }

    function renderTemplate()
    {
        $path = $this->request->getPathInfo();
        $isHidden = array_filter(explode('/', $path), function ($dir) {
            return strlen($dir) > 0 && $dir[0] === '_';
        });
        if ($isHidden) return;

        $templateFile = $path . '.twig';

        $fs = new Filesystem();
        if ($fs->exists('../templates' . $templateFile)) {
            return $this->twig->render($templateFile, []);
        }

        return '404';
    }
}
