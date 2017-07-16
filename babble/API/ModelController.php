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

    public function create(Request $request, $id)
    {
        // If ID is already taken.
        if($this->model->exists($id)) {
            return null;
        }

        $data = json_decode($request->getContent(), true);

        // Save model instance.
        $modelInstance = ModelInstance::fromData($this->model, $id, $data);
        $modelInstance->save();
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
        $data = json_decode($request->getContent(), true);

        // If ID was changed and new ID is already taken.
        $oldId = $data['_old_id'];
        if(!empty($oldId) && $oldId !== $id && $this->model->exists($id)) {
            return null;
        }

        // Save model instance.
        $modelInstance = ModelInstance::fromData($this->model, $id, $data);
        $modelInstance->save();

        // If ID was changed, delete old version.
        if (!empty($oldId) && $oldId !== $id) {
            $deleteInstance = ModelInstance::fromDisk($this->model, $oldId);
            $deleteInstance->delete();
        }

        return json_encode($modelInstance);
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