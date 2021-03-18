<?php

namespace Babble\API;

use Babble\Models\Model;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RootController extends Controller
{
    public function describe(Request $request): JsonResponse
    {
        return new JsonResponse(Model::all());
    }
}