<?php

namespace Babble\API;

use Babble\Exceptions\RecordNotFoundException;
use Babble\Models\Model;
use Babble\Models\Record;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Router
{
    private $router;
    private $dispatcher;
    private $session;

    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->router = new RouteCollection();
        $this->session = new Session();
        $this->addRoutes();
    }

    public function handleRequest(Request $request): Response
    {
        $this->session->start();
        if (!$this->checkAuth($request)) {
            return new JsonResponse(['error' => 'Access denied'], 401);
        }

        $context = new RequestContext($request);
        $matcher = new UrlMatcher($this->router, $context);
        $parameters = $matcher->match($request->getPathInfo());

        header('Content-Type: application/json');
        switch ($parameters['_route']) {
            case 'login':
                return $this->handleLoginRoute($request);
            case 'resources':
                return $this->handleModelRoute($request, $parameters);
            case 'models':
                return $this->handleRootRoute($request);
            case 'files':
                return $this->handleFileRoute($request, $parameters);
        }
    }

    private function checkAuth(Request $request): bool
    {
        $model = new Model('User');

        // Check session auth.
        $sessionUsername = $this->session->get('username');
        if ($sessionUsername) {
            try {
                Record::fromDisk($model, $sessionUsername);
                return true;
            } catch (RecordNotFoundException $e) {
                return false;
            }
        }

        // Check basic auth;
        $authHeader = $request->headers->get('Authorization');
        $authParts = explode(' ', $authHeader, 2);
        if ($authParts[0] !== 'Basic' || count($authParts) !== 2) return false;

        $usernamePassword = explode(':', base64_decode($authParts[1]), 2);
        if (count($usernamePassword) !== 2) return false;

        $username = $usernamePassword[0];
        $password = $usernamePassword[1];

        try {
            $user = Record::fromDisk($model, $username);
        } catch (RecordNotFoundException $e) {
            return false;
        }
        if (!$user->getValue('is_active')) return false;

        $storedHash = $user->getValue('password');
        $ok = password_verify($password, $storedHash);
        if ($ok) {
            $this->session->set('username', $user->getValue('id'));
        }

        return $ok;
    }

    private function addRoutes()
    {
        $loginRoute = new Route('/api/login');
        $this->router->add('login', $loginRoute);

        $modelsRoute = new Route('/api/models');
        $this->router->add('models', $modelsRoute);

        $modelRoute = new Route('/api/models/{model}/{id}', ['id' => null], ['id' => '.+']); // TODO: Limit to safe characters.
        $this->router->add('resources', $modelRoute);

        $fileRoute = new Route('/api/files/{path}', ['path' => null], ['path' => '.+']);
        $this->router->add('files', $fileRoute);
    }

    private function handleLoginRoute($request)
    {
        $sessionUsername = $this->session->get('username');
        if ($sessionUsername) {
            try {
                $model = new Model('User');
                return new JsonResponse(Record::fromDisk($model, $sessionUsername));
            } catch (RecordNotFoundException $e) {
            }
        }

        return new JsonResponse([
            'error' => 'Access denied'
        ], 401);
    }

    private
    function handleModelRoute(Request $request, array $parameters)
    {
        $controller = new ModelController($this->dispatcher, $parameters['model']);
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

    private
    function handleRootRoute($request)
    {
        $controller = new RootController();
        $method = $request->getMethod();

        switch ($method) {
            case 'OPTIONS':
                return $controller->describe($request);
        }
        return null;
    }

    private
    function handleFileRoute($request, array $parameters)
    {
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
            case 'DELETE':
                return $controller->delete($request, $path);
//            case 'OPTIONS':
//                return $controller->describe($request);
        }
        return null;
    }
}