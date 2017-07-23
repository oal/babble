<?php

namespace Babble\API;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Router
{
    private $router;

    public function __construct()
    {
        $this->router = new RouteCollection();
        $this->addRoutes();
    }

    public function handleRequest(Request $request)
    {
        $context = new RequestContext($request);
        $matcher = new UrlMatcher($this->router, $context);
        $parameters = $matcher->match($request->getPathInfo());

        header('Content-Type: application/json');
        switch ($parameters['_route']) {
            case 'resources':
                return $this->handleModelRoute($request, $parameters);
            case 'models':
                return $this->handleRootRoute($request);
            case 'files':
                return $this->handleFileRoute($request, $parameters);
        }

        var_export($parameters);
    }

    private function addRoutes()
    {
        $modelsRoute = new Route('/api/models');
        $this->router->add('models', $modelsRoute);

        $modelRoute = new Route('/api/models/{model}/{id}', ['id' => null]);
        $this->router->add('resources', $modelRoute);

        $fileRoute = new Route('/api/files/{path}', ['path' => null], ['path' => '.+']);
        $this->router->add('files', $fileRoute);
    }

    private function handleModelRoute(Request $request, array $parameters)
    {
        $controller = new ModelController($parameters['model']);
        $method = $request->getMethod();

        $id = $parameters['id'];
        switch ($method) {
            case 'GET':
                return $controller->read($request, $id);
            case 'PUT':
                return $controller->update($request, $id);
            case 'POST':
                return $controller->create($request, $id);
            case 'DELETE':
                return $controller->delete($request, $id);
            case 'OPTIONS':
                return $controller->describe($request);
        }
        return null;
    }

    private function handleRootRoute($request)
    {
        $controller = new RootController();
        $method = $request->getMethod();

        switch ($method) {
            case 'OPTIONS':
                return $controller->describe($request);
        }
        return null;
    }

    private function handleFileRoute($request, array $parameters)
    {
        error_log(json_encode($parameters));
        $controller = new FileController();
        $method = $request->getMethod();

        $path = $parameters['path'];
        switch ($method) {
            case 'GET':
                return $controller->read($request, $path);
            case 'PUT':
                return $controller->update($request, $path);
            case 'POST':
                return $controller->create($request, $path);
//            case 'DELETE':
//                return $controller->delete($request, $id);
//            case 'OPTIONS':
//                return $controller->describe($request);
        }
        return null;
    }
}