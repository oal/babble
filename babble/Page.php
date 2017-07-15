<?php

namespace Babble;

use Symfony\Component\HttpFoundation\Request;
use Twig_Environment;
use Twig_Function;
use Twig_Loader_Filesystem;

class Page
{
    private $request;
    private $model;

    public function __construct(Request $request, Model $model)
    {
        $this->request = $request;
        $this->model = $model;
    }

    function render()
    {
        $loader = new Twig_Loader_Filesystem('../templates');
        $twig = new Twig_Environment($loader, ['debug' => true]);

        $modelNames = ContentLoader::getModelNames();
        foreach ($modelNames as $modelName) {
            $twig->addFunction(new Twig_Function($modelName, function () use ($modelName) {
                return new ContentLoader($modelName);
            }));
        }

        $path = $this->request->getPathInfo();
        $basePath = substr($path, 0, strrpos($path, '/'));

        $modelType = $this->model->getType();
        return $twig->render($basePath . '/' . $modelType . '.twig', [
            'this' => $this->model
        ]);
    }
}
