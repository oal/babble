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

        switch ($parameters['_route']) {
            case 'model':
                return $this->handleModelRoute($request, $parameters);
        }
        var_export($parameters);
    }

    private function addRoutes()
    {
        $modelRoute = new Route('/api/{model}/{id}', ['id' => null]);
        $this->router->add('model', $modelRoute);
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
                return $controller->create($request);
            case 'DELETE':
                return $controller->delete($request, $id);
            case 'OPTIONS':
                return $controller->describe($request);
        }
        return 'NOT FOUND';
    }
}