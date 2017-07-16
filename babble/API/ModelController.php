<?php

namespace Babble\API;

use Babble\Content\ContentLoader;
use Babble\Models\Model;
use Symfony\Component\HttpFoundation\Request;

class ModelController extends Controller
{
    private $modelType;

    public function __construct(string $model)
    {
        $this->modelType = $model;
    }

    public function create(Request $request)
    {
        return 'CREATE';
    }

    public function read(Request $request, $id)
    {
        $loader = new ContentLoader($this->modelType);
        if (!empty($id)) {
            return $loader->find($id);
        }
        return json_encode($loader->get());
    }

    public function update(Request $request, $id)
    {
        return 'UPDATE';
    }

    public function delete(Request $request, $id)
    {
        return 'DELETE';
    }

    public function describe(Request $request)
    {
        $model = new Model($this->modelType);
        return json_encode($model);
    }
}