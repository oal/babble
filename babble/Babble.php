<?php

namespace Babble;

use Babble\API;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Babble
{
    private $debug = false;
    private $renderer;

    public function __construct()
    {
        $this->renderer = new TemplateRenderer();
    }

    /**
     * Debug enables Babble's debug mode, and also enables Symfony's Debug component for prettier stack traces etc.
     */
    public function debug()
    {
        $this->debug = true;
        Debug::enable();
    }

    /**
     * Serves a response based on the request received from the client.
     */
    public function serve()
    {
        $request = Request::createFromGlobals();
        $response = $this->handleRequest($request);
        $response->send();
    }

    private function handleRequest(Request $request): Response
    {
        $path = $request->getPathInfo();

        if (preg_match('/api/', $path)) {
            return $this->handleAPIRequest($request);
        }
        return $this->handlePageRequest($request);
    }

    private function handleAPIRequest(Request $request): Response
    {
        $router = new API\Router();
        return $router->handleRequest($request);
    }

    private function handlePageRequest(Request $request): Response
    {
        $path = $request->getPathInfo();
        return $this->renderer->render($path);
    }
}
