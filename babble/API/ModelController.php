<?php

namespace Babble\API;

use Babble\Content\ContentLoader;
use Babble\Models\Model;
use Babble\Models\Record;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        if ($this->model->exists($id)) {
            return new JsonResponse([
                'error' => 'Provided ID is already taken.'
            ], 400);
        }

        $data = json_decode($request->getContent(), true);

        // Save model instance.
        $record = new Record($this->model, $id, $data);
        $record->save();
        return new JsonResponse($record);
    }

    public function read(Request $request, $id)
    {
        $loader = new ContentLoader($this->model->getType());

        if (!empty($id)) return $this->readOne($loader, $id);
        return $this->readMany($loader);
    }

    private function readOne(ContentLoader $loader, $id)
    {
        try {
            $record = $loader->find($id);
            return new JsonResponse($record);
        } catch (Exception $e) {
        }

        return new Response(null, 404);
    }

    private function readMany(ContentLoader $loader)
    {
        try {
            $records = $loader->get();
            return new JsonResponse($records);
        } catch (Exception $e) {
        }

        return new JsonResponse([]);
    }

    public function update(Request $request, $id)
    {
        $data = json_decode($request->getContent(), true);

        // If ID was changed and new ID is already taken.
        $oldId = $data['_old_id'] ?? null;
        if (!empty($oldId) && $oldId !== $id && $this->model->exists($id)) {
            return new JsonResponse([
                'error' => 'Provided ID is already in use.'
            ], 400);
        }

        // Save model instance.
        $record = Record::fromDisk($this->model, $id);
        $record->update($data);

        $modelInstance = new Record($this->model, $id, $data);
        $modelInstance->save();

        // If ID was changed, delete old version.
        if (!empty($oldId) && $oldId !== $id) {
            $deleteInstance = Record::fromDisk($this->model, $oldId);
            $deleteInstance->delete();
        }

        return new JsonResponse($modelInstance);
    }

    public function delete(Request $request, $id)
    {
        return new JsonResponse([
            'error' => 'Not implemented'
        ], 400);
    }

    public function describe(Request $request)
    {
        return new JsonResponse($this->model);
    }
}