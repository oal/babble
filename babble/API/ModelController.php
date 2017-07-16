<?php

namespace Babble\API;

use Babble\Content\ContentLoader;
use Babble\ModelInstance;
use Babble\Models\Model;
use Symfony\Component\HttpFoundation\Request;

class ModelController extends Controller
{
    private $model;

    public function __construct(string $modelType)
    {
        $this->model = new Model($modelType);
    }

    public function create(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $modelInstance = ModelInstance::fromData($this->model, $data);

        return json_encode($modelInstance);
    }

    public function read(Request $request, $id)
    {
        $loader = new ContentLoader($this->model->getType());
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
        return json_encode($this->model);
    }
}