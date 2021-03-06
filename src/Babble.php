<?php

namespace Babble;

use Babble\API;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Babble
{
    private $config;
    private $dispatcher;
    private $cache;
    private $liveReload = false;

    public function __construct()
    {
        $this->dispatcher = new EventDispatcher();
        $this->loadConfig();
        $this->liveReload = !!getenv('BABBLE_LIVE_RELOAD');
    }

    /**
     * Enables Babble's debug mode, and also enables Symfony's Debug component for prettier stack traces etc.
     */
    private function enableDebug()
    {
        Debug::enable();
    }

    /**
     * Serves a response based on the request received from the client.
     */
    public function serve()
    {
        $request = Request::createFromGlobals();
        $response = $this->handleRequest($request);

        if ($this->liveReload) $this->injectLiveReload($response);

        $response->send();
    }

    private function handleRequest(Request $request): Response
    {
        $path = $request->getPathInfo();

        if (strpos($path, '/api/') === 0) {
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
        $path = new Path($request->getPathInfo());

        // Redirect if needed.
        if (!$path->getExtension()) {
            if (substr($path, -1) !== '/') {
                return new RedirectResponse($path . '/');
            }
            if ($path->getFilename() === 'index') {
                return new RedirectResponse($path->getDirectory() . '/');
            }
        }

        // Attempt to load from cache.
        if ($this->cache) {
            $cachedPage = $this->cache->load($path);
            if ($cachedPage) {
                return new Response($cachedPage);
            }
        }

        // Generate page and serve.
        $renderer = new TemplateRenderer($this->dispatcher);
        $html = $renderer->render($path);
        if ($html === null) {
            return new Response($renderer->renderNotFound(), 404);
        }

        return new Response($html);
    }

    private function loadConfig()
    {
        $request = Request::createFromGlobals();
        $this->config = new Config($request->getHost());

        // Set up cache.
        if ($this->config->get('cache')) {
            $this->cache = new Cache($this->dispatcher);
        }

        if ($this->config->get('debug')) $this->enableDebug();
    }

    /**
     * Injects script tag that loads livereload.js to the response.
     *
     * @param Response $response
     */
    private function injectLiveReload(Response $response)
    {
        $content = $response->getContent();
        $content = str_replace('</body>', '
            <script>
                document.write(\'<script src="http://\' + (location.host || \'localhost\').split(\':\')[0] + \':35729/livereload.js?snipver=1"></\' + \'script>\')
            </script></body>', $content);
        $response->setContent($content);
    }
}
