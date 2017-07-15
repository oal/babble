<?php

namespace Babble;

use Babble\API;
use Babble\Content\ContentLoader;
use Symfony\Component\HttpFoundation\Request;

class Babble
{
    public function __construct()
    {
        $request = Request::createFromGlobals();
        $this->routeRequest($request);
    }

    private function routeRequest(Request $request)
    {
        if (preg_match('/static/', $request->getPathInfo())) {
            return false;
        }
        if (preg_match('/api/', $request->getPathInfo())) {
            $this->routeRequestToAPI($request);
        } else {
            $this->routeRequestToPage($request);
        }
        return true;
    }

    private function routeRequestToAPI(Request $request)
    {
        $router = new API\Router();
        echo $router->handleRequest($request);
    }

    private function routeRequestToPage(Request $request)
    {
        $model = ContentLoader::matchPath($request->getPathInfo());
        $page = new Page($request, $model);
        echo $page->render();
    }
}
