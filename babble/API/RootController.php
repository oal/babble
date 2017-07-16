<?php

namespace Babble\API;

use Babble\Models\Model;
use Symfony\Component\HttpFoundation\Request;

class RootController
{
    public function create(Request $request)
    {
    }

    public function read(Request $request, $id)
    {
    }

    public function update(Request $request, $id)
    {
    }

    public function delete(Request $request, $id)
    {
    }

    public function describe(Request $request)
    {
        return json_encode(Model::all());
    }
}