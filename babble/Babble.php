<?php

namespace Babble;

use Babble\API;
use Symfony\Component\Debug\Debug;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Babble
{
    private $debug = false;
    private $dispatcher;
    private $renderer;
    private $cache;

    public function __construct()
    {
        $this->dispatcher = new EventDispatcher();
        $this->renderer = new TemplateRenderer($this->dispatcher);
        $this->cache = new Cache($this->dispatcher);
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
        $router = new API\Router($this->dispatcher);
        return $router->handleRequest($request);
    }

    private function handlePageRequest(Request $request): Response
    {
        $path = $request->getPathInfo();

        // Attempt to load from cache.
        $cachedPage = $this->cache->load($path);
        if ($cachedPage) {
            return new Response($cachedPage);
        }

        $response = $this->renderer->render($path);
        return $response;
    }
}
