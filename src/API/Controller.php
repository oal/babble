<?php

namespace Babble\API;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class Controller
{
    public function create(Request $request, $id)
    {
        return new JsonResponse([
            'error' => 'Not implemented'
        ], 400);
    }

    public function read(Request $request, $id)
    {
        return new JsonResponse([
            'error' => 'Not implemented'
        ], 400);
    }

    public function update(Request $request, $id)
    {
        return new JsonResponse([
            'error' => 'Not implemented'
        ], 400);
    }

    public function delete(Request $request, $id)
    {
        return new JsonResponse([
            'error' => 'Not implemented'
        ], 400);
    }

    public function describe(Request $request)
    {
        return new JsonResponse([
            'error' => 'Not implemented'
        ], 400);
    }
}