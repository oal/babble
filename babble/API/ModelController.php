<?php

namespace Babble\API;

use Babble\Content\ContentLoader;
use Babble\Models\Model;
use Symfony\Component\HttpFoundation\Request;

class ModelController extends Controller
{
    private $model;

    public function __construct(string $model)
    {
        $this->model = new Model($model);
    }

    public function create(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        return json_encode([
            'valid' => $this->model->validate($data)
        ]);
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