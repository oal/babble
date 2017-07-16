<?php

namespace Babble;

use Babble\API;
use Babble\Content\ContentLoader;
use Babble\Models\Model;
use Symfony\Component\HttpFoundation\Request;

class Babble
{
    public function __construct()
    {
        new Model('Post');
        $request = Request::createFromGlobals();
        $this->routeRequest($request);
    }

    private function routeRequest(Request $request)
    {
        $path = $request->getPathInfo();

        if (preg_match('/api/', $path)) {
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
        $modelInstance = ContentLoader::matchPath($request->getPathInfo());
        $page = new Page($request, $modelInstance);
        echo $page->render();
    }
}
