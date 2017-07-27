<?php

namespace Babble;

use Babble\API;
use Babble\Content\ContentLoader;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;

Debug::enable();

class Babble
{
    public function __construct()
    {
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
        $router->handleRequest($request)->send();
    }

    private function routeRequestToPage(Request $request)
    {
        $renderer = new TemplateRenderer($request);

        $record = ContentLoader::matchPath($request->getPathInfo());
        if ($record !== null) {
            echo $renderer->renderRecord($record);
            return;
        }
        echo $renderer->renderTemplate();
    }
}
