<?php

namespace Babble\API;

use Babble\Content\ContentLoader;
use Symfony\Component\HttpFoundation\Request;

class ModelController
{
    private $model;

    public function __construct(string $model)
    {
        $this->model = $model;
    }

    public function create(Request $request)
    {
        return 'CREATE';
    }

    public function read(Request $request, $id)
    {
        $loader = new ContentLoader($this->model);
        if (!empty($id)) {
            return $loader->find($id);
        }
        return '[' . join(', ', $loader->get()) . ']';
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
        return 'DESCRIBE' . $this->model;
    }
}